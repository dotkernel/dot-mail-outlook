# dot-mail-outlook

Dotkernel's Microsoft Outlook ESMTP email service, implementing the `client credentials` grant_type. 

> dot-mail-outlook is a wrapper on top of [dotkernel/dot-mail](https://github.com/dotkernel/dot-mail)

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-mail-outlook)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-mail-outlook/dev-master)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-mail-outlook)](https://github.com/dotkernel/dot-mail-outlook/blob/0.1/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/continuous-integration.yml/badge.svg?branch=0.1)](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-mail-outlook/graph/badge.svg?token=TiXewEbffE)](https://codecov.io/gh/dotkernel/dot-mail-outlook)
[![PHPStan](https://github.com/dotkernel/dot-mail-outlook/actions/workflows/static-analysis.yml/badge.svg?branch=0.1)](https://github.com/dotkernel/dot-mail/actions/workflows/static-analysis.yml)

## Installation

Install `dotkernel/dot-mail-outlook` by executing the following Composer command:

```shell
composer require dotkernel/dot-mail-outlook
```

Register `src/ConfigProvider.php` in `config/config.php` by adding the following line:

```php
\Dot\MailOutlook\ConfigProvider::class,
```

## Configuration

Copy the `dot-mail-outlook.local` file to your `autoload` folder (or copy its contents to `mail.global.php`) and fill in the relevant information.

```php
<?php

declare(strict_types=1);

$tenant = '';

return [
    'xoauth2_outlook' => [
        "tokenCacheFile"  => '',
        "tenant"          => $tenant,
        "access_code_url" => "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
        "client_id"       => '',
        "client_secret"   => '',
        "scope"           => 'https://outlook.office.com/.default',
        "grant_type"      => 'client_credentials',
    ],
];
```

The `dotkernel/dot-mail` config file should be updated to make sure the necessary options are set:

- `transport` **MUST** be set to under the `dot_mail.default.transport` key.
- `port` **MUST** be set to `587` under the `dot_mail.default.smtp_options.port` key.
- `tls` **MUST** be set to `STARTTLS` under the `dot_mail.default.smtp_options.tls` key.
- `host` **MUST** be one of `smtp-mail.outlook.com` or `smtp.office365.com` under the `dot_mail.default.smtp_options.host` key.

## Additional info

`dotkernel/dot-mail-outlook` makes use of SASL XOAUTH2 mechanism for use with the [SMTP AUTH](https://datatracker.ietf.org/doc/html/rfc4954) command.

To allow generating the bearer token in the background, without user input required,
`dot-mail-outlook` implements the `client credentials` flow from Microsoft:

- [Authenticate an IMAP, POP or SMTP connection using OAuth](https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth)
- [Microsoft identity platform and the OAuth 2.0 client credentials flow](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow)

> Make sure to set all relevant permissions, give relevant tenant administrator consent and register the necessary service principals, as described in Microsoft's flow.
