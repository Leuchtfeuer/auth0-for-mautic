Auth0 for Mautic
================
![Auth0Mautic](https://www.bitmotion.de/fileadmin/github/auth0-for-mautic/Mautic-Auth0.png "Auth0 for Mautic")

This Mautic plugin allows logins and signups via Auth0.

## Installation
1. Open a Terminal / Console window
2. Change directory to the mautic root (i.e. `cd /var/www/mautic`)
3. Clone this repository into plugins/MauticAuth0Bundle (`git clone https://github.com/bitmotion/auth0-for-mautic.git plugins/MauticAuth0Bundle`)
4. Clear the cache (`php app/console cache:clear`)
5. Go to Settings -> Plugins and click on "Install/Upgrade Plugins"
6. Choose the Auth0 Plugin, adapt configuration and publish it

There is also the possibility to add this package directly into your project composer.json file by executing following command: `composer require bitmiotion/mautic-auth0-bundle`.

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
