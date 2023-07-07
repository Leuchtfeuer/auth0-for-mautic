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
    'name'        => 'Auth0 Integration by Leuchtfeuer',
    'description' => 'Enables Auth0 login for users.',
    'version'     => '2.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'services'    => [
        'events' => [
            'mautic.leuchtfeuerauth0.user.subscriber' => [
                'class'     => \MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener\UserSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.leuchtfeuerauth0.config.subscriber' => [
                'class' => \MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener\ConfigSubscriber::class,
            ],
        ],
        'integrations' => [
            'mautic.integration.leuchtfeuerauth0' => [
                'class'     => \MauticPlugin\LeuchtfeuerAuth0Bundle\Integration\LeuchtfeuerAuth0Integration::class,
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
            'mautic.form.type.leuchtfeuerauth0config' => [
                'class' => \MauticPlugin\LeuchtfeuerAuth0Bundle\Form\Type\ConfigType::class,
                'alias' => 'leuchtfeuerauth0config',
            ],
        ],
    ],
    'parameters' => [
        'leuchtfeuerauth0_username'  => 'email',
        'leuchtfeuerauth0_email'     => 'email',
        'leuchtfeuerauth0_firstName' => 'given_name',
        'leuchtfeuerauth0_lastName'  => 'family_name',
        'leuchtfeuerauth0_timezone'  => null,
        'leuchtfeuerauth0_locale'    => null,
        'leuchtfeuerauth0_signature' => null,
        'leuchtfeuerauth0_position'  => null,
        'leuchtfeuerauth0_role'      => 'app_metadata.roles',
        'leuchtfeuerauth0_admin'     => 'user_metadata.admin',
        'multiple_roles'  => 1,
        'rolemapping'     => [
            '0' => 'admin => 1',
            '1' => 'users => 2',
        ],
    ],
];
