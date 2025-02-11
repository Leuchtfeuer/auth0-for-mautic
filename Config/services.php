<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Services',
    ];

    $services->load('MauticPlugin\\MauticAuth0Bundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    // Basic definitions with name, display name and icon
    $services->alias('mautic.integration.auth0', MauticPlugin\MauticAuth0Bundle\Integration\Auth0Integration::class);
    // Provides the form types to use for the configuration UI
//    $services->alias('grapesjsbuilder.integration.configuration', MauticPlugin\MauticAuth0Bundle\Integration\Support\ConfigSupport::class);
//    // Tells Mautic what themes it should support when enabled
//    $services->alias('grapesjsbuilder.integration.builder', MauticPlugin\MauticAuth0Bundle\Integration\Support\BuilderSupport::class);
};