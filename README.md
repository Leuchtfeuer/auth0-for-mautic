# Auth0 Integration by Leuchtfeuer

This Mautic plugin allows logins and sign ups via Auth0.


## Requirements for this release
> [!TIP]
> Other releases of this plugin may cover different Mautic versions!
- Mautic 6

## Installation
### Composer
This plugin can be installed through composer.
### Manual Installation
Alternatively, it can be installed manually, following the usual steps:
- Download the plugin
- Unzip to the Mautic `plugins` directory
- Rename folder to `LeuchtfeuerAuth0Bundle`
- In the Mautic backend, go to the `Plugins` page as an administrator
- Click on the `Install/Upgrade Plugins` button to install the Plugin.
OR
- If you have shell access, execute `php bin\console cache:clear` and `php bin\console mautic:plugins:reload` to install the plugins.
Don't forget to activate the plugin in the plugin settings.

## Configuration
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

### Auth0 Configuration
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

## Update from Mautic 4.x.x to Mautic 5.x.x
When updating the plugin, please make sure to change the callback URL from `../s/sso_login/Auth0` to `../s/sso_login/LeuchtfeuerAuth0` in your Auth0 application settings.

## Known Issues

## Troubleshooting
Make sure you have not only installed but also enabled the Plugin.
If things are still funny, please try
`php bin/console cache:clear`


## Change log
- https://github.com/Leuchtfeuer/auth0-for-mautic/releases
## Future Ideas
---Mention any planned updates, features, or ideas for future development.---
## Sponsoring & Commercial Support
We are continuously improving our plugins. If you are requiring priority support or custom features, please contact us at mautic-plugins@leuchtfeuer.com.
## Get Involved
Feel free to open issues or submit pull requests on [GitHub](#). Follow the contribution guidelines in `CONTRIBUTING.md`.”
## Credits

## Author
Leuchtfeuer Digital Marketing GmbH
Please raise any issues in GitHub.
For all other things, please email mautic-plugins@Leuchtfeuer.com
## License
“This plugin is licensed under the MIT License. See the `LICENSE` file for more details.”
## Resources / Further Readings

