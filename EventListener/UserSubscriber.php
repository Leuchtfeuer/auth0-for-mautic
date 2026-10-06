<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected CoreParametersHelper $coreParametersHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_PRE_AUTHENTICATION => ['onUserAuthentication', 0],
        ];
    }

    public function onUserAuthentication(AuthenticationEvent $event): void
    {
        $authenticatingService = $event->getAuthenticatingService();

        if (LeuchtfeuerAuth0Integration::NAME !== $authenticatingService) {
            return;
        }

        try {
            $this->authenticate($event, $authenticatingService);
        } catch (\Throwable $exception) {
            $this->logger->error('Auth0 login failed: '.$exception->getMessage(), ['exception' => $exception]);

            throw $exception;
        }
    }

    private function authenticate(AuthenticationEvent $event, string $authenticatingService): void
    {
        $integration = $event->getIntegration($authenticatingService);

        if (!$integration instanceof LeuchtfeuerAuth0Integration) {
            throw new \RuntimeException('The integration is not found.');
        }

        $integration->setCoreParametersHelper($this->coreParametersHelper);
        $integration->setUserProvider($event->getUserProvider());

        $loginCheck = (bool) $event->isLoginCheck();
        if ($loginCheck) {
            $this->logCallbackError($event->getRequest());
        }

        $result = $this->authenticateService($integration, $loginCheck);

        if ($result instanceof User) {
            $event->setIsAuthenticated($authenticatingService, $result, $integration->shouldAutoCreateNewUser());
        } elseif ($result instanceof Response) {
            $event->setResponse($result);
        }
    }

    private function logCallbackError(Request $request): void
    {
        $reason = $request->query->get('error_description') ?? $request->query->get('error');
        if (!is_string($reason) || '' === $reason) {
            $reason = $request->request->get('error_description') ?? $request->request->get('error');
        }

        if (is_string($reason) && '' !== $reason) {
            $this->logger->error('Auth0 login failed: '.$reason);
        }
    }

    private function authenticateService(LeuchtfeuerAuth0Integration $integration, bool $loginCheck): RedirectResponse|bool|User
    {
        if ($loginCheck) {
            /** @var false|User $authenticatedUser */
            $authenticatedUser = $integration->ssoAuthCallback();
            if ($authenticatedUser instanceof User) {
                return $authenticatedUser;
            }
        } else {
            $loginUrl = $integration->getAuthLoginUrl();

            return new RedirectResponse($loginUrl);
        }

        return false;
    }
}
