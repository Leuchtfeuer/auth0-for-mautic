<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

if (version_compare(MAUTIC_VERSION, '5', '<')) {
    return;
}

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('MauticPlugin\\LeuchtfeuerAuth0Bundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');
    $services->alias('mautic.integration.leuchtfeuerauth0', MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration::class);
};
