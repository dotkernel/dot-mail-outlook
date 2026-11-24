# Overview

`dotkernel/dot-mail-outlook` lets [dotkernel/dot-mail](https://github.com/dotkernel/dot-mail) send email through Microsoft Outlook and Microsoft 365 SMTP servers with OAuth 2.0.

Microsoft SMTP servers accept the SASL XOAUTH2 mechanism of the [SMTP AUTH](https://datatracker.ietf.org/doc/html/rfc4954) command.
This package gets the bearer token with the Microsoft `client credentials` flow, so no user input is necessary.

## Components

| Class | Purpose |
| --- | --- |
| `Dot\MailOutlook\Service\XOauthTokenProviderService` | Gets an access token from Microsoft and keeps it in a cache file until it expires |
| `Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator` | Sends the `AUTH XOAUTH2` command with the mailbox name and the access token |
| `Dot\MailOutlook\DotMailServiceDelegator` | Sets the authenticator on the ESMTP transport of a dot-mail service |
| `Dot\MailOutlook\ConfigProvider` | Registers the two services and the delegator for `dot-mail.service.default` |

## Flow

1. The container creates `dot-mail.service.default`.
2. `DotMailServiceDelegator` receives the service. If the transport is an `EsmtpTransport`, the delegator sets `XOauth2OutlookAuthenticator` as its only authenticator.
3. When the transport connects, the authenticator asks `XOauthTokenProviderService` for a token.
4. The token provider returns the cached token if it is valid for at least 60 more seconds. If not, it requests a new token from Microsoft and writes it to the cache file.

## Microsoft documentation

- [Authenticate an IMAP, POP or SMTP connection using OAuth](https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth)
- [Microsoft identity platform and the OAuth 2.0 client credentials flow](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow)
