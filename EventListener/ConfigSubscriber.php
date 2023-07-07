<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\LeuchtfeuerAuth0Bundle\Form\Type\ConfigType;
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
            'bundle'     => 'LeuchtfeuerAuth0Bundle',
            'formAlias'  => 'leuchtfeuerauth0config',
            'formTheme'  => 'LeuchtfeuerAuth0Bundle:FormTheme\Config',
            'formType'   => ConfigType::class,
            'parameters' => $event->getParametersFromConfig('LeuchtfeuerAuth0Bundle'),
        ]);
    }
}
