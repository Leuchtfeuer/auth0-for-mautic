<?php

namespace MauticPlugin\MauticAuth0Bundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\MauticAuth0Bundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'MauticAuth0Bundle',
            'formAlias'  => 'auth0config',
            'formTheme'  => 'MauticAuth0Bundle:FormTheme\Config',
            'formType'   => ConfigType::class,
            'parameters' => $event->getParametersFromConfig('MauticAuth0Bundle'),
        ]);
    }
}
