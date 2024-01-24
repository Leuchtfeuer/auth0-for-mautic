<?php

return [
    'name'        => 'Auth0 Integration by Leuchtfeuer',
    'description' => 'Enables Auth0 login for users.',
    'version'     => '2.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'parameters'  => [
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
