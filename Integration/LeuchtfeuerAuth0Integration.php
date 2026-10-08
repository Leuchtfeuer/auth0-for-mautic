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
use MauticPlugin\LeuchtfeuerAuth0Bundle\Helper\LoginFailure;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeuchtfeuerAuth0Integration extends AbstractSsoServiceIntegration
{
    public const NAME = 'LeuchtfeuerAuth0';

    protected ClientInterface $client;

    /**
     * @var array<string, mixed>
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
        if (isset($this->keys['domain']) && is_string($this->keys['domain'])) {
            return 'https://'.$this->keys['domain'].'/authorize';
        }

        return '';
    }

    public function getAuthScope(): string
    {
        return 'openid profile read:current_user';
    }

    public function getAccessTokenUrl(): string
    {
        if (isset($this->keys['domain']) && is_string($this->keys['domain'])) {
            return 'https://'.$this->keys['domain'].'/oauth/token';
        }

        return '';
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
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
        );
    }

    /**
     * @param string|bool|array<string>|mixed $response
     *
     * @return false|User
     *
     * @throws \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUser($response): bool|User
    {
        if (!isset($this->keys['domain']) || !is_string($this->keys['domain'])) {
            throw new \RuntimeException('The domain key must be set.');
        }
        $this->setClient('https://'.rtrim($this->keys['domain'], '/').'/');

        if (!is_array($response)) {
            throw new \RuntimeException('The response for getUser must be an array.');
        }

        try {
            $userInfo        = $this->getUserInfo($response);
            $managementToken = $this->getManagementToken();

            if (!array_key_exists('token_type', $managementToken) || !array_key_exists('access_token', $managementToken)) {
                $this->failLogin('Auth0 management token is missing token_type or access_token.');
            }

            $subject = $userInfo['sub'] ?? null;
            if (!is_string($subject) || '' === $subject) {
                $this->failLogin('Auth0 userinfo response did not include a subject.');
            }

            $auth0User = $this->getAuth0User($subject, $managementToken);

            if (isset($auth0User['user_id']) && $auth0User['user_id'] === $subject) {
                $this->auth0User = $auth0User;

                return $this->createMauticUserFromAuth0User();
            }

            $this->failLogin($this->translator->trans('plugin.auth0.login_failed_subject_mismatch'));
        } catch (GuzzleException $exception) {
            $this->failLogin('request to Auth0 failed: '.$exception->getMessage(), $exception, $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string>        $keys
     */
    protected function getAuth0ValueRecursive(array $data, array $keys): mixed
    {
        $actualKey = array_shift($keys);

        if (isset($data[$actualKey])) {
            if (is_array($data[$actualKey]) && count($keys) > 0) {
                /** @var array<string, mixed> $nestedData */
                $nestedData = $data[$actualKey];

                return $this->getAuth0ValueRecursive($nestedData, $keys);
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
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    protected function getUserInfo(array $token): array
    {
        if (!array_key_exists('token_type', $token) || !array_key_exists('access_token', $token)) {
            $this->failLogin('Auth0 access token is missing token_type or access_token.');
        }

        if (!is_string($token['token_type']) || !is_string($token['access_token'])) {
            throw new \RuntimeException('The token must be a string.');
        }

        $response = $this->client->request(
            'GET',
            'userinfo',
            [
                'headers' => [
                    'Authorization' => $token['token_type'].' '.$token['access_token'],
                ],
                'http_errors' => false,
            ]
        );

        return $this->decodeAuth0Response('userinfo', $response);
    }

    /**
     * @return array<mixed>
     *
     * @throws GuzzleException
     */
    protected function getManagementToken(): array
    {
        if (!array_key_exists('audience', $this->keys) || !array_key_exists('domain', $this->keys)) {
            $this->failLogin('Auth0 domain or audience is not configured.');
        }

        if (!is_string($this->keys['audience']) || !is_string($this->keys['domain'])) {
            throw new \RuntimeException('The token must be a string.');
        }

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
        );

        return $this->decodeAuth0Response('management token', $response);
    }

    /**
     * @param array<mixed> $managementToken
     *
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    protected function getAuth0User(string $userId, array $managementToken): array
    {
        if (
            !array_key_exists('audience', $this->keys)
            || !array_key_exists('token_type', $managementToken)
            || !array_key_exists('access_token', $managementToken)
        ) {
            $this->failLogin('Auth0 management token is missing token_type or access_token.');
        }

        if (
            !is_string($this->keys['audience'])
            || !is_string($managementToken['token_type'])
            || !is_string($managementToken['access_token'])
        ) {
            throw new \RuntimeException('The token must be a string.');
        }

        $response = $this->client->request(
            'GET',
            trim($this->keys['audience'], '/').'/users/'.$userId,
            [
                'headers' => [
                    'Authorization' => $managementToken['token_type'].' '.$managementToken['access_token'],
                ],
                'http_errors' => false,
            ]
        );

        return $this->decodeAuth0Response('user profile', $response);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createMauticUserFromAuth0User(): User
    {
        $mauticUser = null;

        // Find existing user
        try {
            $mauticUser = $this->userProvider->loadUserByIdentifier($this->getStringValue('auth0_username', 'email'));
        } catch (\Throwable) {
            // No User found. Do nothing.
        }

        if (!$mauticUser instanceof User) {
            // Create new user if there is no existing user
            $mauticUser = new User();
        }

        $role = $this->getUserRole();

        if (null === $role || $role instanceof Role) {
            $mauticUser->setRole($role);
        }

        // Override user data by data provided by auth0
        $mauticUser
            ->setUsername($this->getStringValue('auth0_username', 'email'))
            ->setEmail($this->getStringValue('auth0_email', 'email'))
            ->setFirstName($this->getStringValue('auth0_firstName', 'given_name'))
            ->setLastName($this->getStringValue('auth0_lastName', 'family_name'))
            ->setTimezone($this->getStringValue('auth0_timezone'))
            ->setLocale($this->getStringValue('auth0_locale'))
            ->setSignature($this->getStringValue('auth0_signature'))
            ->setPosition($this->getStringValue('auth0_position'));

        if ('' === $mauticUser->getFirstName()) {
            $mauticUser->setFirstName('Auth0 First Name');
        }

        if ('' === $mauticUser->getLastName()) {
            $mauticUser->setLastName('Auth0 Last Name');
        }

        $auth0Role = $this->setValueFromAuth0User('auth0_role');
        if (is_array($auth0Role)) {
            $auth0RoleIdentifier = array_shift($auth0Role);
            if (is_numeric($auth0RoleIdentifier)) {
                $roleRepository = $this->em->getRepository(Role::class);
                $mauticRole     = $roleRepository->find($auth0RoleIdentifier);
                if (null !== $mauticRole) {
                    $mauticUser->setRole($mauticRole);
                }
            }
        }

        return $mauticUser;
    }

    protected function setValueFromAuth0User(string $configurationParameter, string $fallback = ''): mixed
    {
        $configParameter = $this->coreParametersHelper->get($configurationParameter);

        if (null === $configParameter) {
            return $this->auth0User[$fallback] ?? '';
        }

        if (!is_string($configParameter)) {
            throw new \RuntimeException('The config value "'.$configurationParameter.'" must contain a string.');
        }

        $value = $this->getAuth0ValueRecursive(
            $this->auth0User,
            explode('.', $configParameter)
        );

        // Fallback if there is no username
        if ('' === $value && '' !== $fallback) {
            $value = $this->auth0User[$fallback] ?? '';
        }

        return $value;
    }

    private function getStringValue(string $configurationParameter, string $fallback = ''): string
    {
        $value = $this->setValueFromAuth0User($configurationParameter, $fallback);

        if (!is_string($value)) {
            throw new \RuntimeException('The value "'.$configurationParameter.'" must be a string.');
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

    /**
     * @phpstan-ignore missingType.iterableValue (inherited from parent class)
     */
    public function encryptAndSetApiKeys(array $keys, \Mautic\PluginBundle\Entity\Integration $entity): void
    {
        if (isset($keys['domain']) && is_string($keys['domain'])) {
            $keys['domain'] = $this->normalizeDomain($keys['domain']);
        }

        parent::encryptAndSetApiKeys($keys, $entity);
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);

        // Remove protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);

        if (null === $domain) {
            throw new \RuntimeException('Failed to normalize domain.');
        }

        // Remove trailing slashes
        $domain = rtrim($domain, '/');

        return $domain;
    }

    /**
     * Auth0 error bodies stay in the response because requests are sent with http_errors disabled.
     *
     * @return array<string, mixed>
     */
    private function decodeAuth0Response(string $step, ResponseInterface $response): array
    {
        $status = $response->getStatusCode();

        try {
            $decoded = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException(sprintf('Auth0 %s returned HTTP %d with a body that is not JSON.', $step, $status));
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Auth0 %s returned HTTP %d with an unexpected body.', $step, $status));
        }

        $reason = $this->describeAuth0Error($decoded, $status);
        if (null !== $reason) {
            $this->failLogin(
                sprintf('Auth0 %s failed: %s', $step, $reason),
                null,
                $this->auth0ErrorMessage($decoded) ?? '',
            );
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<mixed> $payload
     */
    private function describeAuth0Error(array $payload, int $status): ?string
    {
        $bodyStatus = $payload['statusCode'] ?? null;
        $hasError   = isset($payload['error']) || (is_int($bodyStatus) && $bodyStatus >= 400);

        if ($status < 400 && !$hasError) {
            return null;
        }

        $details = [];
        if ($status >= 400) {
            $details[] = 'HTTP '.$status;
        } elseif (is_int($bodyStatus) && $bodyStatus >= 400) {
            $details[] = 'HTTP '.$bodyStatus;
        }

        $message = $this->auth0ErrorMessage($payload);
        if (null !== $message) {
            $details[] = $message;
        }

        return [] === $details ? 'HTTP '.$status : implode(': ', $details);
    }

    /**
     * @param array<mixed> $payload
     */
    private function auth0ErrorMessage(array $payload): ?string
    {
        $parts = [];
        foreach (['error', 'error_description', 'message', 'errorCode'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && '' !== $value) {
                $parts[] = $value;
            }
        }

        return [] === $parts ? null : implode(': ', $parts);
    }

    private function failLogin(string $reason, ?\Throwable $previous = null, ?string $userMessage = null): never
    {
        LoginFailure::report($this->logger, $reason, $previous, $userMessage);
    }
}
