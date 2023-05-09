# Auth0 Integration by Leuchtfeuer
![Auth0Mautic](https://www.leuchtfeuer.com/fileadmin/github/auth0-for-mautic/Mautic-Auth0.png "Auth0 for Mautic")


[![Latest Stable Version](https://poser.pugx.org/leuchtfeuer/mautic-auth0-bundle/v/stable)](https://packagist.org/packages/leuchtfeuer/mautic-auth0-bundle)
[![Build Status](https://github.com/Leuchtfeuer/auth0-for-mautic/workflows/Continous%20Integration/badge.svg)](https://github.com/Leuchtfeuer/auth0-for-mautic/actions)
[![Total Downloads](https://poser.pugx.org/leuchtfeuer/mautic-auth0-bundle/downloads)](https://packagist.org/leuchtfeuer/mautic-auth0-bundle)
[![Latest Unstable Version](https://poser.pugx.org/leuchtfeuer/mautic-auth0-bundle/v/unstable)](https://packagist.org/leuchtfeuer/mautic-auth0-bundle)
[![Code Climate](https://codeclimate.com/github/Leuchtfeuer/auth0-for-mautic/badges/gpa.svg)](https://codeclimate.com/github/Leuchtfeuer/auth0-for-mautic)
[![License](https://poser.pugx.org/leuchtfeuer/mautic-auth0-bundle/license)](https://packagist.org/packages/leuchtfeuer/mautic-auth0-bundle)

This Mautic plugin allows logins and sign ups via Auth0.

## Installation
1. Open a Terminal / Console window
2. Change directory to the mautic root (i.e. `cd /var/www/mautic`)
3. Clone this repository into plugins/MauticAuth0Bundle (`git clone https://github.com/Leuchtfeuer/auth0-for-mautic.git plugins/MauticAuth0Bundle`)
4. Clear the cache (`php app/console cache:clear`)
5. Go to Settings -> Plugins and click on "Install/Upgrade Plugins"
6. Choose the Auth0 Plugin, adapt the configuration and publish it

There is also the possibility to add this package directly into your project composer.json file by executing following command: `composer require leuchtfeuer/mautic-auth0-bundle`.

We are currently supporting following Mautic versions:<br><br>

| Bundle Version | Mautic v3 Support | Mautic v2 Support |
| :-: | :-: | :-: |
| 1.1.x          | x                 | -                 |
| 1.0.x          | -                 | x                 |

### Plugin Configuration
<table>
    <tr>
        <th>Configuration</th>
        <th>Description</th>
    </tr>
    <tr>
        <td>domain</td>
        <td>Auth0 Domain</td>
    </tr>
    <tr>
        <td>audience</td>
        <td>Link to audience (should be /api/v2)
    </tr>
    <tr>
        <td>client_id</td>
        <td>ID of the client</td>
    </tr>
    <tr>
        <td>client_secret</td>
        <td>Secret of the client</td>
    </tr>
</table>

## Configuration
You can configure the mapping (Auth0 data -> Mautic User data) in the configuration module. There are several options:

<table>
    <tr>
        <th>Configuration</th>
        <th>Title</th>
        <th>Default (Auth0) Value</th>
    </tr>
    <tr>
        <td>auth0_username</td>
        <td>Username</td>
        <td>email</td>
    </tr>
    <tr>
        <td>auth0_email</td>
        <td>Email</td>
        <td>email</td>
    </tr>
    <tr>
        <td>auth0_firstName</td>
        <td>First Name</td>
        <td>given_name</td>
    </tr>
    <tr>
        <td>auth0_lastName</td>
        <td>Last Name</td>
        <td>family_name</td>
    </tr>
    <tr>
        <td>auth0_signature</td>
        <td>Signature</td>
        <td></td>
    </tr>
    <tr>
        <td>auth0_position</td>
        <td>Position</td>
        <td></td>
    </tr>
    <tr>
        <td>auth0_timezone</td>
        <td>Timezone</td>
        <td></td>
    </tr>
    <tr>
        <td>auth0_locale</td>
        <td>Language</td>
        <td></td>
    </tr>
</table>

Use dot syntax to access arrays (i.e. `user_metadata.login_name`).

### Roles

If you want to map a role from Auth0 to your Mautic-User you have to alter your app_metadata in your Auth0-User 
(where `<ROLE_ID>` is the ID of your Mautic-Role):

```metadata json
{
    ...
    "mautic": {
        "role": <ROLE_ID>
    }
}
```

## Update from Mautic 2.x.x to Mautic 3.x.x
When updating the plugin, please make sure to change the callback URL from `../s/sso_login/Auth0Auth` to `../s/sso_login/Auth0` in your Auth0 application settings.

### Author
Leuchtfeuer Digital Marketing GmbH

mautic@Leuchtfeuer.com