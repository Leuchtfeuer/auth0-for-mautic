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
    'version'     => '1.1.1',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'services'    => [
        'forms' => [
            'mautic.form.type.auth0config' => [
                'class' => \MauticPlugin\MauticAuth0Bundle\Form\Type\ConfigType::class,
                'alias' => 'auth0config',
            ],
        ],
    ],
    'parameters' => [
        'auth0_username'  => 'email',
        'auth0_email'     => 'email',
        'auth0_firstName' => 'given_name',
        'auth0_lastName'  => 'family_name',
        'auth0_timezone'  => null,
        'auth0_locale'    => null,
        'auth0_signature' => null,
        'auth0_position'  => null,
        'auth0_role'      => 'app_metadata.roles',
        'auth0_admin'     => 'user_metadata.admin',
        'multiple_roles'  => 1,
        'rolemapping'     => [
            '0' => 'admin => 1',
            '1' => 'users => 2',
        ],
    ],
];
