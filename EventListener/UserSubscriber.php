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
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            UserEvents::USER_PRE_AUTHENTICATION => ['onUserAuthentication', 0],
        ];
    }

    public function onUserAuthentication(AuthenticationEvent $event)
    {
        $result                = false;
        $authenticatingService = $event->getAuthenticatingService();

        if ('Auth0' === $authenticatingService) {
            $integration = $event->getIntegration($authenticatingService);

            if ($integration instanceof LeuchtfeuerAuth0Integration) {
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
    private function authenticateService(LeuchtfeuerAuth0Integration $integration, $loginCheck)
    {
        if ($loginCheck) {
            if ($authenticatedUser = $integration->ssoAuthCallback()) {
                return $authenticatedUser;
            }
        } else {
            $loginUrl = $integration->getAuthLoginUrl();
            $response = new RedirectResponse($loginUrl);

            return $response;
        }

        return false;
    }
}
