<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Provider\UserProvider;
use MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener\UserSubscriber;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class UserSubscriberTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;
    private UserSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->subscriber           = new UserSubscriber($this->coreParametersHelper);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = UserSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('mautic.user_pre_authentication', $events);
        self::assertEquals(['onUserAuthentication', 0], $events['mautic.user_pre_authentication']);
    }

    public function testSuccessfulLoginCheckAuthenticatesUser(): void
    {
        // ========================================
        // Setup: Create Mock User
        // ========================================
        $authenticatedUser = $this->createMock(User::class);

        // ========================================
        // Setup: Mock Auth0 Integration
        // ========================================
        $integration = $this->createMock(LeuchtfeuerAuth0Integration::class);
        $integration->expects(self::once())
            ->method('setCoreParametersHelper')
            ->with($this->coreParametersHelper);

        $integration->expects(self::once())
            ->method('setUserProvider')
            ->with(self::isInstanceOf(UserProvider::class));

        // ssoAuthCallback() returns authenticated user
        $integration->expects(self::once())
            ->method('ssoAuthCallback')
            ->willReturn($authenticatedUser);

        $integration->expects(self::once())
            ->method('shouldAutoCreateNewUser')
            ->willReturn(true);

        // ========================================
        // Setup: Mock AuthenticationEvent
        // ========================================
        $event = $this->createMock(AuthenticationEvent::class);
        $event->expects(self::once())
            ->method('getAuthenticatingService')
            ->willReturn(LeuchtfeuerAuth0Integration::NAME);

        $event->expects(self::once())
            ->method('getIntegration')
            ->with(LeuchtfeuerAuth0Integration::NAME)
            ->willReturn($integration);

        $event->expects(self::once())
            ->method('getUserProvider')
            ->willReturn($this->createMock(UserProvider::class));

        $event->expects(self::once())
            ->method('isLoginCheck')
            ->willReturn(true);

        // Verify user is authenticated
        $event->expects(self::once())
            ->method('setIsAuthenticated')
            ->with(LeuchtfeuerAuth0Integration::NAME, $authenticatedUser, true);

        $event->expects(self::never())
            ->method('setResponse');

        // ========================================
        // ACT: Trigger Event
        // ========================================
        $this->subscriber->onUserAuthentication($event);
    }

    public function testNonLoginCheckTriggersRedirectToAuth0(): void
    {
        // ========================================
        // Setup: Mock Auth0 Integration
        // ========================================
        $integration = $this->createMock(LeuchtfeuerAuth0Integration::class);
        $integration->expects(self::once())
            ->method('setCoreParametersHelper')
            ->with($this->coreParametersHelper);

        $integration->expects(self::once())
            ->method('setUserProvider')
            ->with(self::isInstanceOf(UserProvider::class));

        // getAuthLoginUrl() returns Auth0 login URL
        $integration->expects(self::once())
            ->method('getAuthLoginUrl')
            ->willReturn('https://example.eu.auth0.com/authorize?client_id=123&redirect_uri=...');

        $integration->expects(self::never())
            ->method('ssoAuthCallback');

        // ========================================
        // Setup: Mock AuthenticationEvent
        // ========================================
        $event = $this->createMock(AuthenticationEvent::class);
        $event->expects(self::once())
            ->method('getAuthenticatingService')
            ->willReturn(LeuchtfeuerAuth0Integration::NAME);

        $event->expects(self::once())
            ->method('getIntegration')
            ->with(LeuchtfeuerAuth0Integration::NAME)
            ->willReturn($integration);

        $event->expects(self::once())
            ->method('getUserProvider')
            ->willReturn($this->createMock(UserProvider::class));

        $event->expects(self::once())
            ->method('isLoginCheck')
            ->willReturn(false); // NOT a login check = initial auth request

        // Verify redirect response is set
        $event->expects(self::once())
            ->method('setResponse')
            ->with(self::isInstanceOf(RedirectResponse::class));

        $event->expects(self::never())
            ->method('setIsAuthenticated');

        // ========================================
        // ACT: Trigger Event
        // ========================================
        $this->subscriber->onUserAuthentication($event);
    }

    public function testFailedAuthenticationDoesNotAuthenticateUser(): void
    {
        // ========================================
        // Setup: Mock Auth0 Integration - Returns FALSE
        // ========================================
        $integration = $this->createMock(LeuchtfeuerAuth0Integration::class);
        $integration->expects(self::once())
            ->method('setCoreParametersHelper')
            ->with($this->coreParametersHelper);

        $integration->expects(self::once())
            ->method('setUserProvider')
            ->with(self::isInstanceOf(UserProvider::class));

        // ssoAuthCallback() returns FALSE (auth failed)
        $integration->expects(self::once())
            ->method('ssoAuthCallback')
            ->willReturn(false);

        $integration->expects(self::never())
            ->method('shouldAutoCreateNewUser');

        // ========================================
        // Setup: Mock AuthenticationEvent
        // ========================================
        $event = $this->createMock(AuthenticationEvent::class);
        $event->expects(self::once())
            ->method('getAuthenticatingService')
            ->willReturn(LeuchtfeuerAuth0Integration::NAME);

        $event->expects(self::once())
            ->method('getIntegration')
            ->with(LeuchtfeuerAuth0Integration::NAME)
            ->willReturn($integration);

        $event->expects(self::once())
            ->method('getUserProvider')
            ->willReturn($this->createMock(UserProvider::class));

        $event->expects(self::once())
            ->method('isLoginCheck')
            ->willReturn(true);

        // Verify user is NOT authenticated
        $event->expects(self::never())
            ->method('setIsAuthenticated');

        $event->expects(self::never())
            ->method('setResponse');

        // ========================================
        // ACT: Trigger Event
        // ========================================
        $this->subscriber->onUserAuthentication($event);
    }
}
