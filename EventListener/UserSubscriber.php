<?php

namespace MauticPlugin\MauticAuth0Bundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\MauticAuth0Bundle\Integration\Auth0Integration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserSubscriber implements EventSubscriberInterface
{

    public function __construct(protected CoreParametersHelper $coreParametersHelper)
    {
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_PRE_AUTHENTICATION => ['onUserAuthentication', 0],
        ];
    }

    public function onUserAuthentication(AuthenticationEvent $event): void
    {
        $result                = false;
        $authenticatingService = $event->getAuthenticatingService();


        if ('Auth0' === $authenticatingService) {

            $integration = $event->getIntegration($authenticatingService);

            if ($integration instanceof Auth0Integration) {

                $integration->setCoreParametersHelper($this->coreParametersHelper);

                $integration->setUserProvider($event->getUserProvider());
                $result = $this->authenticateService($integration, $event->isLoginCheck());
            }

            if ($result instanceof User) {

                $event->setIsAuthenticated($authenticatingService, $result, $integration->shouldAutoCreateNewUser());
            } elseif ($result instanceof Response) {
                $event->setResponse($result);
            }
        }
    }

    /**
     * @param $loginCheck
     *
     * @return bool|RedirectResponse
     */
    private function authenticateService(Auth0Integration $integration, $loginCheck): RedirectResponse|bool
    {
        if ($loginCheck) {
            if ($authenticatedUser = $integration->ssoAuthCallback()) {
                return $authenticatedUser;
            }
        } else {
            $loginUrl = $integration->getAuthLoginUrl();
            return new RedirectResponse($loginUrl);
        }

        return false;
    }
}
