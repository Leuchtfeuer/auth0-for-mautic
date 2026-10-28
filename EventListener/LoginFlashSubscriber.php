<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Mautic 5 positions the login growl absolutely inside the panel, which clips it.
 * Mautic 7 already uses fixed positioning. Override only the login panel so other
 * core notifications stay untouched on both versions.
 */
final class LoginFlashSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['onInjectCustomAssets', 0],
        ];
    }

    public function onInjectCustomAssets(CustomAssetsEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || 'login' !== $request->attributes->get('_route')) {
            return;
        }

        $event->addStyleDeclaration(
            '#main-panel-flash-msgs .alert-growl-container{position:fixed;top:20px;right:20px;z-index:100000;}'
        );
    }
}
