## Installation

Uses the "client credentials" flow from Microsoft for generating the bearer token:
* https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow
* https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth

Copy the `dot-mail-outlook.local` file to your `autoload` folder (or copy its contents to `mail.global.php`) and fill in the relevant information.

`Dot-mail` configuration **MUST** use the `esmtp` transport under the `dot_mail.default.transport` key.

`Dot-mail` configuration **MUST** use the `smtp.office365.com` host under the `dot_mail.default.smtp_options.host` key.

> Make sure the port is set to 587

`Dot-mail` configuration **MUST** be updated to set `tls` to `STARTTLS` under the `dot_mail.default.smtp_options.tls` key, for the `AUTH XOAUTH` command.
