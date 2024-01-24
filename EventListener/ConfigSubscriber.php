<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'LeuchtfeuerAuth0Bundle',
            'formAlias'  => 'auth0config',
            'formTheme'  => '@LeuchtfeuerAuth0/FormTheme/Config/_config_auth0config_widget.html.twig',
            'formType'   => ConfigType::class,
            'parameters' => $event->getParametersFromConfig('LeuchtfeuerAuth0Bundle'),
        ]);
    }
}
