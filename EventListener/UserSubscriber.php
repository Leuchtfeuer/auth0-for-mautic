<?php

namespace MauticPlugin\MauticAuth0Bundle\EventListener;


use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\MauticAuth0Bundle\Integration\Auth0AuthIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserSubscriber
 *
 * @package MauticPlugin\MauticAuth0Bundle\EventListener
 */
class UserSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * UserSubscriber constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
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
            UserEvents::USER_PRE_AUTHENTICATION => ['onUserAuthentication', 0,],
        ];
    }

    /**
     * @param AuthenticationEvent $event
     */
    public function onUserAuthentication(AuthenticationEvent $event)
    {
        $result = false;
        $authenticatingService = $event->getAuthenticatingService();

        if ($authenticatingService === 'Auth0Auth') {
            $integration = $event->getIntegration($authenticatingService);

            if ($integration instanceof Auth0AuthIntegration) {
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
     * @param Auth0AuthIntegration $integration
     * @param                               $loginCheck
     *
     * @return bool|RedirectResponse
     */
    private function authenticateService(Auth0AuthIntegration $integration, $loginCheck)
    {
        if ($loginCheck) {
            if ($authenticatedUser = $integration->ssoAuthCallback()) {
                return $authenticatedUser;
            }
        }
        else {
            $loginUrl = $integration->getAuthLoginUrl();
            $response = new RedirectResponse($loginUrl);

            return $response;
        }

        return false;
    }
}