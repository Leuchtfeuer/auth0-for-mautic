<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Integration\AbstractSsoServiceIntegration;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;

class LeuchtfeuerAuth0Integration extends AbstractSsoServiceIntegration
{
    public const NAME = 'LeuchtfeuerAuth0';

    protected ClientInterface $client;

    /**
     * @var array<string|int|bool, array<string|int|bool|array<string|int|bool>>>
     */
    protected array $auth0User = [];

    protected CoreParametersHelper $coreParametersHelper;

    protected UserProvider $userProvider;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerAuth0Bundle/Assets/img/leuchtfeuer-mautic-auth0.png';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     */
    public function getAuthenticationType(): string
    {
        return 'oauth2';
    }

    public function getAuthenticationUrl(): string
    {
        return 'https://'.$this->keys['domain'].'/authorize';
    }

    public function getAuthScope(): string
    {
        return 'openid profile read:current_user';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://'.$this->keys['domain'].'/oauth/token';
    }

    public function shouldAutoCreateNewUser(): bool
    {
        return true;
    }

    /**
     * Set in the UserSubscriber.
     */
    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper): void
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Set in the UserSubscriber.
     */
    public function setUserProvider(UserProvider $userProvider): void
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Set the callback URL to sso_login.
     */
    public function getAuthCallbackUrl(): string
    {
        return sprintf(
            '%s://%s%s',
            $this->router->getContext()->getScheme(),
            $this->router->getContext()->getHost(),
            $this->router->generate('mautic_sso_login_check',
                ['integration' => $this->getName()],
                true // absolute
            )
        );
    }

    /**
     * @param string|bool $response
     *
     * @return false|User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUser($response): bool|User
    {
        $this->setClient('https://'.rtrim($this->keys['domain'], '/').'/');

        try {
            $userInfo        = $this->getUserInfo($response);
            $managementToken = $this->getManagementToken();

            if (!array_key_exists('token_type', $managementToken) || !array_key_exists('access_token', $managementToken)) {
                throw new AuthenticationServiceException('Management token');
            }

            $auth0User = $this->getAuth0User($userInfo['sub'], $managementToken);
        } catch (GuzzleException) {
            return false;
        }

        if (isset($auth0User['user_id']) && $auth0User['user_id'] === $userInfo['sub']) {
            // There is a user
            $this->auth0User = $auth0User;

            return $this->createMauticUserFromAuth0User();
        }

        return false;
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $keys
     *
     * @return string|int|bool|array<string|int|bool>
     */
    protected function getAuth0ValueRecursive(array $data, array $keys): array|bool|int|string
    {
        $actualKey = array_shift($keys);

        if (isset($data[$actualKey])) {
            if (is_array($data[$actualKey]) && count($keys) > 0) {
                return $this->getAuth0ValueRecursive($data[$actualKey], $keys);
            }

            return $data[$actualKey];
        }

        return '';
    }

    protected function setClient(string $baseUri): void
    {
        $this->client = new Client(['base_uri' => $baseUri]);
    }

    /**
     * @param array<mixed> $token
     *
     * @return array<mixed>
     *
     * @throws GuzzleException
     */
    protected function getUserInfo(array $token): array
    {
        $response = $this->client->request(
            'GET',
            'userinfo',
            [
                'headers' => [
                    'Authorization' => $token['token_type'].' '.$token['access_token'],
                ],
                'http_errors' => false,
            ]
        )->getBody()->getContents();

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<mixed>
     *
     * @throws GuzzleException
     */
    protected function getManagementToken(): array
    {
        $response = $this->client->request(
            'POST',
            'oauth/token',
            [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->keys['client_id'],
                    'client_secret' => $this->keys['client_secret'],
                    'audience'      => 'https://'.rtrim($this->keys['domain'], '/').'/'.trim($this->keys['audience'], '/').'/',
                ],
                'http_errors' => false,
            ]
        )->getBody()->getContents();

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<mixed> $managementToken
     *
     * @return array<mixed>
     *
     * @throws GuzzleException
     */
    protected function getAuth0User(string $userId, array $managementToken): array
    {
        $response = $this->client->request(
            'GET',
            trim($this->keys['audience'], '/').'/users/'.$userId,
            [
                'headers' => [
                    'Authorization' => $managementToken['token_type'].' '.$managementToken['access_token'],
                ],
                'http_errors' => false,
            ]
        )->getBody()->getContents();

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createMauticUserFromAuth0User(): User
    {
        $mauticUser = null;

        // Find existing user
        try {
            $mauticUser = $this->userProvider->loadUserByUsername($this->setValueFromAuth0User('auth0_username', 'email'));
        } catch (\Throwable) {
            // No User found. Do nothing.
        }

        if (!$mauticUser instanceof User) {
            // Create new user if there is no existing user
            $mauticUser = new User();
        }

        // Override user data by data provided by auth0
        $mauticUser
            ->setUsername($this->setValueFromAuth0User('auth0_username', 'email'))
            ->setEmail($this->setValueFromAuth0User('auth0_email', 'email'))
            ->setFirstName($this->setValueFromAuth0User('auth0_firstName', 'given_name'))
            ->setLastName($this->setValueFromAuth0User('auth0_lastName', 'family_name'))
            ->setTimezone($this->setValueFromAuth0User('auth0_timezone'))
            ->setLocale($this->setValueFromAuth0User('auth0_locale'))
            ->setSignature($this->setValueFromAuth0User('auth0_signature'))
            ->setPosition($this->setValueFromAuth0User('auth0_position'))
            ->setRole(
                $this->getUserRole()
            );

        $auth0Role = $this->setValueFromAuth0User('auth0_role');
        if (is_array($auth0Role)) {
            $auth0RoleIdentifier = array_shift($auth0Role);
            if (is_numeric($auth0RoleIdentifier)) {
                $roleRepository = $this->em->getRepository(Role::class);
                $mauticRole = $roleRepository->find($auth0RoleIdentifier);
                if (null !== $mauticRole) {
                    $mauticUser->setRole($mauticRole);
                }
            }
        }

        return $mauticUser;
    }

    /**
     * @return string|bool|int|array<string|bool|int>
     */
    protected function setValueFromAuth0User(string $configurationParameter, string $fallback = ''): array|bool|int|string
    {
        $value = $this->getAuth0ValueRecursive(
            $this->auth0User,
            explode('.', $this->coreParametersHelper->get($configurationParameter))
        );

        // Fallback if there is no username
        if ('' === $value && '' !== $fallback) {
            $value = $this->auth0User[$fallback] ?? '';
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'domain'        => 'plugin.auth0.integration.keyfield.domain',
            'audience'      => 'plugin.auth0.integration.keyfield.audience',
            'client_id'     => 'plugin.auth0.integration.keyfield.client_id',
            'client_secret' => 'plugin.auth0.integration.keyfield.client_secret',
        ];
    }
}
