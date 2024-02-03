<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Tests\Integration;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\ClientInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class LeuchtfeuerAuth0IntegrationTest extends TestCase
{
    public function testGetUserHappyPath(): void
    {
        $newUserRole     = $this->createMock(Role::class);
        $apiUserRole     = $this->createMock(Role::class);
        $newUserRoleName = '101101';
        $apiRoleId       = '176254252246';
        $accessSettings  = ['domain' => 'dom.ain', 'client_secret' => 'Secret!', 'client_id' => 'client id!', 'audience' => 'The audience'];
        $token           = ['token_type' => 'type', 'access_token' => 'access'];

        $integration = $this->getMockBuilder(LeuchtfeuerAuth0Integration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setClient', 'getDecryptedApiKeys'])
            ->getMock();

        $integration->method('getDecryptedApiKeys')
            ->willReturn($accessSettings);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnMap([
                ['auth0_username', null, 'username'],
                ['auth0_email', null, 'email'],
                ['auth0_firstName', null, ''],
                ['auth0_lastName', null, ''],
                ['auth0_timezone', null, ''],
                ['auth0_locale', null, 'locale'],
                ['auth0_signature', null, ''],
                ['auth0_position', null, ''],
                ['auth0_role', null, 'app_metadata.roles'],
            ]);

        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects(self::atLeastOnce())
            ->method('find')
            ->with($apiRoleId)
            ->willReturn($apiUserRole);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::atLeastOnce())
            ->method('getReference')
            ->with(Role::class, $newUserRoleName)
            ->willReturn($newUserRole);
        $em->expects(self::atLeastOnce())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($roleRepository);

        $reflectionObject   = new \ReflectionObject($integration);
        $reflectionProperty = $reflectionObject->getProperty('em');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($integration, $em);

        $settings = $this->createMock(Integration::class);
        $settings->method('getFeatureSettings')
            ->willReturn(['new_user_role' => $newUserRoleName]);

        $this->getClient($integration, $accessSettings, $token, ['app_metadata' => ['roles' => [$apiRoleId]]]);

        $integration->setIntegrationSettings($settings);
        $integration->setCoreParametersHelper($coreParametersHelper);
        $user = $integration->getUser($token);
        self::assertInstanceOf(User::class, $user);

        self::assertSame('some@email.com', $user->getEmail());
        self::assertSame('some@email.com', $user->getUserIdentifier());
        self::assertSame('First', $user->getFirstName());
        self::assertSame('Last', $user->getLastName());
        self::assertSame('', $user->getTimezone());
        self::assertSame('de', $user->getLocale());
        self::assertSame('', $user->getSignature());
        self::assertSame('', $user->getPosition());
        self::assertSame($apiUserRole, $user->getRole());
    }

    public function testGetUserErrorRole(): void
    {
        $newUserRole     = $this->createMock(Role::class);
        $newUserRoleName = '101101';
        $accessSettings  = ['domain' => 'dom.ain', 'client_secret' => 'Secret!', 'client_id' => 'client id!', 'audience' => 'The audience'];
        $token           = ['token_type' => 'type', 'access_token' => 'access'];

        $integration = $this->getMockBuilder(LeuchtfeuerAuth0Integration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setClient', 'getDecryptedApiKeys'])
            ->getMock();

        $integration->method('getDecryptedApiKeys')
            ->willReturn($accessSettings);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnMap([
                ['auth0_username', null, 'username'],
                ['auth0_email', null, 'email'],
                ['auth0_firstName', null, ''],
                ['auth0_lastName', null, ''],
                ['auth0_timezone', null, ''],
                ['auth0_locale', null, 'locale'],
                ['auth0_signature', null, ''],
                ['auth0_position', null, ''],
                ['auth0_role', null, 'app_metadata.roles'],
            ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::atLeastOnce())
            ->method('getReference')
            ->with(Role::class, $newUserRoleName)
            ->willReturn($newUserRole);
        $em->expects(self::never())
            ->method('getRepository');

        $reflectionObject   = new \ReflectionObject($integration);
        $reflectionProperty = $reflectionObject->getProperty('em');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($integration, $em);

        $settings = $this->createMock(Integration::class);
        $settings->method('getFeatureSettings')
            ->willReturn(['new_user_role' => $newUserRoleName]);

        $this->getClient($integration, $accessSettings, $token, []);

        $integration->setIntegrationSettings($settings);
        $integration->setCoreParametersHelper($coreParametersHelper);
        $user = $integration->getUser($token);
        self::assertInstanceOf(User::class, $user);

        self::assertSame('some@email.com', $user->getEmail());
        self::assertSame('some@email.com', $user->getUserIdentifier());
        self::assertSame('First', $user->getFirstName());
        self::assertSame('Last', $user->getLastName());
        self::assertSame('', $user->getTimezone());
        self::assertSame('de', $user->getLocale());
        self::assertSame('', $user->getSignature());
        self::assertSame('', $user->getPosition());
        self::assertSame($newUserRole, $user->getRole());
    }

    /**
     * @param array<mixed> $data
     *
     * @return MockObject&ResponseInterface
     */
    private function getGuzzleResponse(array $data)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn(json_encode($data, JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')
            ->willReturn($stream);

        return $response;
    }

    /**
     * @param array<string, string> $accessSettings
     * @param array<string, string> $token
     * @param array<mixed>          $userDataReplacement
     *
     * @return ClientInterface&MockObject
     */
    private function getClient(LeuchtfeuerAuth0Integration $integration, array $accessSettings, array $token, array $userDataReplacement)
    {
        $userInfoSub    = 'some_service|some-id-101';
        $managementData = ['token_type' => 'Token type', 'access_token' => 'Access token'];
        $userData       = [
            'email'          => 'some@email.com',
            'email_verified' => true,
            'name'           => 'First1 Last1',
            'given_name'     => 'First',
            'family_name'    => 'Last',
            'picture'        => 'https://lh3.googleusercontent.com/a/one-ne',
            'locale'         => 'de',
            'updated_at'     => '2024-01-31T08:11:48.233Z',
            'user_id'        => $userInfoSub,
            'nickname'       => 'nick.name',
            'identities'     => [
                0 => [
                    'provider'   => explode('|', $userInfoSub)[0],
                    'user_id'    => explode('|', $userInfoSub)[1],
                    'connection' => explode('|', $userInfoSub)[1],
                    'isSocial'   => true,
                ],
            ],
            'created_at'    => '2018-08-08T08:09:07.143Z',
            'user_metadata' => [
                    'admin'      => true,
                    'foo'        => 'bar',
                    'login_name' => 'the.login',
                    'name'       => 'First2 Last2, Company name',
                ],
            'idp_tenant_domain' => 'tenant.com',
            'app_metadata'      => [
                    'is_signup' => true,
                    'roles'     => [
                            0 => 'admin',
                        ],

                    'authorization' => [
                            'groups' => [
                                ],
                        ],
                ],
            'last_ip'      => '127.0.3.16',
            'last_login'   => '2024-01-29T15:50:43.556Z',
            'logins_count' => 147,
        ];

        $userData = array_replace_recursive($userData, $userDataReplacement);

        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::exactly(3))
            ->method('request')
            ->willReturnCallback(function (string $method, string $uri, array $parameters) use ($managementData, $userInfoSub, $userData, $accessSettings, $token): ResponseInterface {
                if ('userinfo' === $uri) {
                    self::assertSame('GET', $method);

                    self::assertSame([
                        'headers' => [
                            'Authorization' => $token['token_type'].' '.$token['access_token'],
                        ],
                        'http_errors' => false,
                    ], $parameters);

                    return $this->getGuzzleResponse(['sub' => $userInfoSub]);
                }

                if ('oauth/token' === $uri) {
                    self::assertSame('POST', $method);

                    self::assertSame([
                        'form_params' => [
                            'grant_type'    => 'client_credentials',
                            'client_id'     => $accessSettings['client_id'],
                            'client_secret' => $accessSettings['client_secret'],
                            'audience'      => 'https://'.rtrim($accessSettings['domain'], '/').'/'.trim($accessSettings['audience'], '/').'/',
                        ],
                        'http_errors' => false,
                    ], $parameters);

                    return $this->getGuzzleResponse($managementData);
                }

                if (trim($accessSettings['audience'], '/').'/users/'.$userInfoSub === $uri) {
                    self::assertSame('GET', $method);

                    self::assertSame([
                        'headers' => [
                            'Authorization' => $managementData['token_type'].' '.$managementData['access_token'],
                        ],
                        'http_errors' => false,
                    ], $parameters);

                    return $this->getGuzzleResponse($userData);
                }

                self::fail('Unknown URI '.$uri);
            });

        $reflectionObject   = new \ReflectionObject($integration);
        $reflectionProperty = $reflectionObject->getProperty('client');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($integration, $client);

        return $client;
    }
}
