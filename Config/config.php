<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name' => 'Auth0',
    'description' => 'Enables Auth0 login for users.',
    'version' => '1.1.0',
    'author' => 'Florian Wessels',
    'services' => [
        'events' => [
            'mautic.auth0.user.subscriber' => [
                'class' => \MauticPlugin\MauticAuth0Bundle\EventListener\UserSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.auth0.config.subscriber' => [
                'class' => \MauticPlugin\MauticAuth0Bundle\EventListener\ConfigSubscriber::class,
            ],
        ],
        'integrations' => [
            'mautic.integration.auth0' => [
                'class' => \MauticPlugin\MauticAuth0Bundle\Integration\Auth0Integration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.auth0config' => [
                'class' => \MauticPlugin\MauticAuth0Bundle\Form\Type\ConfigType::class,
                'alias' => 'auth0config',
            ],
        ],
    ],
    'parameters' => [
        'auth0_username' => 'email',
        'auth0_email' => 'email',
        'auth0_firstName' => 'given_name',
        'auth0_lastName' => 'family_name',
        'auth0_timezone' => null,
        'auth0_locale' => null,
        'auth0_signature' => null,
        'auth0_position' => null,
        'auth0_role' => 'app_metadata.roles',
        'auth0_admin' => 'user_metadata.admin',
        'multiple_roles' => 1,
        'rolemapping' => array(
            '0' => 'admin => 1',
            '1' => 'users => 2'
        ),
    ],
];
