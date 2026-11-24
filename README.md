# dot-mail-outlook

Dotkernel's Microsoft Outlook ESMTP email service, implementing the `client credentials` grant_type.

> dot-mail-outlook is a wrapper on top of [dotkernel/dot-mail](https://github.com/dotkernel/dot-mail)

## Versions

| Branch | Release | Status                                 | PHP        |
|--------|---------|----------------------------------------|------------|
| 2.0    | 2.x     | Active                                 | 8.3 to 8.6 |
| master | 0.x     | Not supported. There is no 1.x release | 8.2 to 8.5 |

To upgrade from 0.x, read [Upgrading from 0.x](https://github.com/dotkernel/dot-mail-outlook/blob/2.0/docs/book/v2/upgrading.md).

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-mail-outlook)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-mail-outlook/dev-2.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/blob/2.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/continuous-integration.yml/badge.svg?branch=2.0)](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-mail-outlook/graph/badge.svg?token=TiXewEbffE)](https://codecov.io/gh/dotkernel/dot-mail-outlook)
[![PHPStan](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/static-analysis.yml/badge.svg?branch=2.0)](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/static-analysis.yml)

## Installation

Install `dotkernel/dot-mail-outlook` by executing the following Composer command:

```shell
composer require dotkernel/dot-mail-outlook
```

Register `src/ConfigProvider.php` in `config/config.php` by adding the following line:

```php
\Dot\MailOutlook\ConfigProvider::class,
```

The package works with any PSR-11 container that supports factories and delegators, for example Laminas Service Manager.
If your application uses Laminas Service Manager, your application must require it.

## Configuration

Copy `config/dot-mail-outlook.local.php.dist` to your `config/autoload` folder as `dot-mail-outlook.local.php` (or copy its contents to `mail.global.php`) and fill in the relevant information.

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

The values of `tokenCacheFile`, `access_code_url`, `client_id`, `client_secret`, `scope` and `grant_type` must be non-empty strings.
If one of them is empty or missing, the package throws an `InvalidArgumentException` that names the key.

Update the `dotkernel/dot-mail` config file to set these options:

- Set `dot_mail.default.transport` to `esmtp`.
- Set `dot_mail.default.smtp_options.host` to `smtp-mail.outlook.com` or `smtp.office365.com`.
- Set `dot_mail.default.smtp_options.port` to `587`.
- Set `dot_mail.default.smtp_options.connection_config.username` to the mailbox that sends the email.
- Keep `dot_mail.default.smtp_options.connection_config.tls` as `null`, so that the transport uses STARTTLS.

## Additional info

`dotkernel/dot-mail-outlook` makes use of SASL XOAUTH2 mechanism for use with the [SMTP AUTH](https://datatracker.ietf.org/doc/html/rfc4954) command.

To allow generating the bearer token in the background, without a user input required,
`dot-mail-outlook` implements the `client credentials` flow from Microsoft:

- [Authenticate an IMAP, POP or SMTP connection using OAuth](https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth)
- [Microsoft identity platform and the OAuth 2.0 client credentials flow](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow)

> Make sure to set all relevant permissions, give relevant tenant administrator consent and register the necessary service principals, as described in Microsoft's flow.

## Documentation

Read the full documentation in [docs/book/v2](https://github.com/dotkernel/dot-mail-outlook/tree/2.0/docs/book/v2).
