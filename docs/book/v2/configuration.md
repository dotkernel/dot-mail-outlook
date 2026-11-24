# Configuration

## Package configuration

Copy `config/dot-mail-outlook.local.php.dist` to `config/autoload/dot-mail-outlook.local.php`.
Then fill in the values.

```php
<?php

declare(strict_types=1);

// Set the tenant before access_code_url, because access_code_url contains it.
$tenant = '';

return [
    'xoauth2_outlook' => [
        // A writable file path. The package creates the file with mode 0600.
        "tokenCacheFile"  => getcwd() . '/data/cache/outlook-token.json',
        "tenant"          => $tenant,
        "access_code_url" => "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
        "client_id"       => '',
        "client_secret"   => '',
        "scope"           => 'https://outlook.office.com/.default',
        "grant_type"      => 'client_credentials',
    ],
];
```

| Key | Required | Meaning |
| --- | --- | --- |
| `tokenCacheFile` | yes | The file that keeps the access token. Its directory must exist and be writable |
| `tenant` | no | Your Microsoft Entra tenant ID. The package does not read it. The config file uses it to build `access_code_url` |
| `access_code_url` | yes | The token endpoint of your tenant |
| `client_id` | yes | The application (client) ID of your app registration |
| `client_secret` | yes | A client secret of your app registration |
| `scope` | yes | Keep `https://outlook.office.com/.default` |
| `grant_type` | yes | Keep `client_credentials` |

Each required value must be a non-empty string.
If one of them is empty or missing, the package throws an `InvalidArgumentException` when the container creates the token provider.
The message names the key, for example `The configuration value "xoauth2_outlook.client_id" must be a non-empty string.`

Keep `client_secret` in a `*.local.php` file and do not commit it.

## The token cache file

The package writes the token to a temporary file with mode 0600, then renames the temporary file to `tokenCacheFile`.
Thus, other users on the server cannot read the token, and a reader never sees a file that is only partly written.

If the cache file is missing, corrupt or expired, the package requests a new token.
Run all workers that send email as the same user, so that they can all read the cache file.

## dot-mail configuration

Set these options in the `dotkernel/dot-mail` config file, for example `config/autoload/mail.global.php`:

| Key | Value |
| --- | --- |
| `dot_mail.default.transport` | `esmtp` |
| `dot_mail.default.smtp_options.host` | `smtp-mail.outlook.com` or `smtp.office365.com` |
| `dot_mail.default.smtp_options.port` | `587` |
| `dot_mail.default.smtp_options.connection_config.username` | The mailbox that sends the email |
| `dot_mail.default.smtp_options.connection_config.tls` | `null`, so that the transport uses STARTTLS |

```php
'dot_mail' => [
    'default' => [
        'transport'    => 'esmtp',
        'smtp_options' => [
            'host'              => 'smtp.office365.com',
            'port'              => 587,
            'connection_config' => [
                'username' => 'sender@example.com',
                'password' => '',
                'tls'      => null,
            ],
        ],
    ],
],
```

The authenticator sends `username` as the `user=` value of the XOAUTH2 command.
The password is not used.

## Microsoft configuration

Register an application in Microsoft Entra, give it the SMTP permission, give tenant administrator consent and register the service principal in Exchange Online.
Follow the steps in [Authenticate an IMAP, POP or SMTP connection using OAuth](https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth).
