<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Integration;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Integration\AbstractSsoServiceIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;

class LeuchtfeuerAuth0Integration extends AbstractSsoServiceIntegration
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $leuchtfeuerauth0User = [];

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var UserProvider
     */
    protected $userProvider;

    public const PLUGIN_NAME = 'LeuchtfeuerAuth0';
    public const DISPLAY_NAME = 'Auth0';

    public function getName()
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName()
    {
        return self::DISPLAY_NAME;
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://'.$this->keys['domain'].'/authorize';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'openid profile read:current_user';
    }

    /**
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://'.$this->keys['domain'].'/oauth/token';
    }

    /**
     * @return bool
     */
    public function shouldAutoCreateNewUser()
    {
        return true;
    }

    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function setUserProvider(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Set the callback URL to sso_login.
     */
    public function getAuthCallbackUrl()
    {
        return sprintf(
            '%s://%s%s',
            $this->router->getContext()->getScheme(),
            $this->router->getContext()->getHost(),
            $this->router->generate('mautic_sso_login_check',
                ['integration' => $this->getName()],
                true //absolute
            )
        );
    }

    /**
     * @param array $response
     *
     * @return bool|User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUser($response)
    {
        $this->setClient('https://'.rtrim($this->keys['domain'], '/').'/');

        try {
            $userInfo        = $this->getUserInfo($response);
            $managementToken = $this->getManagementToken();

            if (!array_key_exists('token_type', $managementToken) || !array_key_exists('access_token', $managementToken)) {
                throw new AuthenticationServiceException('Management token');
            }

            $leuchtfeuerauth0User       = $this->getAuth0User($userInfo['sub'], $managementToken);
        } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
            return false;
        }

        if (is_array($leuchtfeuerauth0User) && isset($leuchtfeuerauth0User['user_id']) && $leuchtfeuerauth0User['user_id'] === $userInfo['sub']) {
            // There is a user
            $this->leuchtfeuerauth0User = $leuchtfeuerauth0User;

            return $this->createMauticUserFromAuth0User();
        }

        return false;
    }

    /**
     * @param $data
     * @param $keys
     *
     * @return string
     */
    protected function getAuth0ValueRecursive($data, $keys)
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

    protected function setClient($baseUri)
    {
        $this->client = new Client(['base_uri' => $baseUri]);
    }

    /**
     * @param $token
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserInfo($token)
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

        return \GuzzleHttp\json_decode($response, true);
    }

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getManagementToken()
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

        return \GuzzleHttp\json_decode($response, true);
    }

    /**
     * @param $userId
     * @param $managementToken
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getAuth0User($userId, $managementToken)
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

        return \GuzzleHttp\json_decode($response, true);
    }

    /**
     * @return User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createMauticUserFromAuth0User()
    {
        $mauticUser = null;

        // Find existing user
        try {
            $mauticUser = $this->userProvider->loadUserByUsername($this->setValueFromAuth0User('leuchtfeuerauth0_username', 'email'));
        } catch (\Exception $exception) {
            // No User found. Do nothing.
        }

        if (!$mauticUser instanceof User) {
            // Create new user if there is no existing user
            $mauticUser = new User();
        }

        // Override user data by data provided by leuchtfeuerauth0
        $mauticUser
            ->setUsername($this->setValueFromAuth0User('leuchtfeuerauth0_username', 'email'))
            ->setEmail($this->setValueFromAuth0User('leuchtfeuerauth0_email', 'email'))
            ->setFirstName($this->setValueFromAuth0User('leuchtfeuerauth0_firstName', 'given_name'))
            ->setLastName($this->setValueFromAuth0User('leuchtfeuerauth0_lastName', 'family_name'))
            ->setTimezone($this->setValueFromAuth0User('leuchtfeuerauth0_timezone'))
            ->setLocale($this->setValueFromAuth0User('leuchtfeuerauth0_locale'))
            ->setSignature($this->setValueFromAuth0User('leuchtfeuerauth0_signature'))
            ->setPosition($this->setValueFromAuth0User('leuchtfeuerauth0_position'))
            ->setRole(
                $this->getUserRole()
            );

        $leuchtfeuerauth0Role = $this->setValueFromAuth0User('leuchtfeuerauth0_role');
        if ($leuchtfeuerauth0Role) {
            $roleRepository = $this->em->getRepository('MauticUserBundle:Role');
            $mauticRole     = $roleRepository->findOneBy(['id' => $leuchtfeuerauth0Role]);
            if ($mauticRole) {
                $mauticUser->setRole($mauticRole);
            }
        }

        return $mauticUser;
    }

    /**
     * @param string $configurationParameter
     * @param string $fallback
     *
     * @return mixed|string
     */
    protected function setValueFromAuth0User($configurationParameter, $fallback = '')
    {
        $value = $this->getAuth0ValueRecursive(
            $this->leuchtfeuerauth0User,
            explode('.', $this->coreParametersHelper->get($configurationParameter))
        );

        // Fallback if there is no username
        if ('' === $value && '' !== $fallback) {
            $value = isset($this->leuchtfeuerauth0User[$fallback]) ? $this->leuchtfeuerauth0User[$fallback] : '';
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'domain'        => 'plugin.leuchtfeuerauth0.integration.keyfield.domain',
            'audience'      => 'plugin.leuchtfeuerauth0.integration.keyfield.audience',
            'client_id'     => 'plugin.leuchtfeuerauth0.integration.keyfield.client_id',
            'client_secret' => 'plugin.leuchtfeuerauth0.integration.keyfield.client_secret',
        ];
    }
}
