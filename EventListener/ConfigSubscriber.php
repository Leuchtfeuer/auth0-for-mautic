<?php

namespace MauticPlugin\MauticAuth0Bundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class ConfigSubscriber
 *
 * @package MauticPlugin\MauticAuth0Bundle\EventListener
 */
class ConfigSubscriber extends CommonSubscriber
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
            'parameters' => $event->getParametersFromConfig('MauticAuth0Bundle'),
        ]);
    }
}
