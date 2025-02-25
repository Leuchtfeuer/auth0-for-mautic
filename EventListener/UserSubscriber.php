<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(protected CoreParametersHelper $coreParametersHelper)
    {
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

    /**
     * @return void
     */
    public function onUserAuthentication(AuthenticationEvent $event)
    {
        $authenticatingService = $event->getAuthenticatingService();

        if (LeuchtfeuerAuth0Integration::NAME === $authenticatingService) {
            $integration = $event->getIntegration($authenticatingService);

            if (!$integration instanceof LeuchtfeuerAuth0Integration) {
                throw new \RuntimeException('The integration is not found.');
            }

            $integration->setCoreParametersHelper($this->coreParametersHelper);
            $integration->setUserProvider($event->getUserProvider());

            $result = $this->authenticateService($integration, $event->isLoginCheck());

            if ($result instanceof User) {
                $event->setIsAuthenticated($authenticatingService, $result, $integration->shouldAutoCreateNewUser());
            } elseif ($result instanceof Response) {
                $event->setResponse($result);
                echo $result->getContent();
                exit;
            }
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
