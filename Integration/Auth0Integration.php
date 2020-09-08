<?php

namespace MauticPlugin\MauticAuth0Bundle\Integration;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Integration\AbstractSsoServiceIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;

class Auth0Integration extends AbstractSsoServiceIntegration
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $auth0User = [];

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var UserProvider
     */
    protected $userProvider;

    public function getName()
    {
        return 'Auth0';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        // Just use none for now and I'll build in "basic" later
        return 'oauth2';
    }

    /**
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://' . $this->keys['domain'] . '/authorize';
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
        return 'https://' . $this->keys['domain'] . '/oauth/token';
    }

    /**
     * @return bool
     */
    public function shouldAutoCreateNewUser()
    {
        return true;
    }

    /**
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param UserProvider $userProvider
     */
    public function setUserProvider(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }


    /**
     * Set the callback URL to sso_login.
     */
    public function getAuthCallbackUrl()
    {
        $end = $this->router->generate('mautic_sso_login_check',
            ['integration' => $this->getName()],
            true //absolute
        );

        $start = $this->router->getContext()->getHost();

        return $this->router->getContext()->getScheme(). "://" . $start . $end;
    }

    /**
     * @param array $response
     *
     * @return bool|User
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUser($response)
    {
        $this->setClient('https://' . rtrim($this->keys['domain'], '/') . '/');

        try {
            $userInfo = $this->getUserInfo($response);
            $managementToken = $this->getManagementToken();
            $auth0User = $this->getAuth0User($userInfo['sub'], $managementToken);
        } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
            return false;
        }

        if (is_array($auth0User) && isset($auth0User['user_id']) && $auth0User['user_id'] === $userInfo['sub']) {
            // There is a user
            $this->auth0User = $auth0User;
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserInfo($token)
    {
        $response = $this->client->request(
            'GET',
            'userinfo',
            [
                'headers' => [
                    'Authorization' => $token['token_type'] . ' ' . $token['access_token'],
                ],
                'http_errors' => false,
            ]
        )->getBody()->getContents();

        return \GuzzleHttp\json_decode($response, true);
    }



    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getManagementToken()
    {
        $response = $this->client->request(
            'POST',
            'oauth/token',
            [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->keys['client_id'],
                    'client_secret' => $this->keys['client_secret'],
                    'audience' => 'https://' . rtrim($this->keys['domain'], '/') . '/' . trim($this->keys['audience'], '/') . '/'
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getAuth0User($userId, $managementToken)
    {
        $response = $this->client->request(
            'GET',
            trim($this->keys['audience'], '/') . '/users/' . $userId,
            [
                'headers' => [
                    'Authorization' => $managementToken['token_type'] . ' ' . $managementToken['access_token'],
                ],
                'http_errors' => false,
            ]
        )->getBody()->getContents();

        return \GuzzleHttp\json_decode($response, true);
    }

    /**
     * @return User
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createMauticUserFromAuth0User()
    {
        $mauticUser = null;

        // Find existing user
        try {
            $mauticUser = $this->userProvider->loadUserByUsername($this->setValueFromAuth0User('auth0_username', 'email'));
        } catch (\Exception $exception) {
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
        if ($auth0Role) {
            $roleRepository = $this->em->getRepository('MauticUserBundle:Role');
            $mauticRole = $roleRepository->findOneBy(['id' => $auth0Role]);
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
            $this->auth0User,
            explode('.', $this->coreParametersHelper->get($configurationParameter))
        );

        // Fallback if there is no username
        if ($value === '' && $fallback !== '') {
            $value = $this->auth0User[$fallback];
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'domain' => 'plugin.auth0.integration.keyfield.domain',
            'audience' => 'plugin.auth0.integration.keyfield.audience',
            'client_id' => 'plugin.auth0.integration.keyfield.client_id',
            'client_secret' => 'plugin.auth0.integration.keyfield.client_secret',
        ];
    }
}