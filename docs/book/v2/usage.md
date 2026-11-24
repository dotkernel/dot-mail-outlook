# Usage

## Send email

After [installation](installation.md) and [configuration](configuration.md), send email with dot-mail as usual.
The package adds the authentication. Your code does not change.

```php
use Dot\Mail\Service\MailServiceInterface;

/** @var MailServiceInterface $mailService */
$mailService = $container->get('dot-mail.service.default');
$mailService->getMessage()->addTo('recipient@example.com');
$mailService->setSubject('Hello');
$mailService->setBody('Sent through Microsoft 365.');
$mailService->send();
```

## Use the package with another mail service

`Dot\MailOutlook\ConfigProvider` adds the delegator only to `dot-mail.service.default`.
To use Outlook authentication with another dot-mail service, add the delegator to that service in your configuration:

```php
'dependencies' => [
    'delegators' => [
        'dot-mail.service.newsletter' => [
            \Dot\MailOutlook\DotMailServiceDelegator::class,
        ],
    ],
],
```

The delegator changes only services with an `EsmtpTransport`.
For other transports, for example `sendmail`, it returns the service without a change, and it does not need the `xoauth2_outlook` configuration.

## Errors

| Exception | When | What to do |
| --- | --- | --- |
| `InvalidArgumentException` | A required `xoauth2_outlook` value is empty or missing | Fill in the value that the message names |
| `RuntimeException` "Microsoft did not return an access token (HTTP status ...)" | Microsoft refused the request | Read the Microsoft error description in the message, then fix the app registration or the configuration |
| `RuntimeException` "... not JSON (HTTP status ...)" | The token endpoint did not return JSON | Check `access_code_url` and the network |
| `RuntimeException` "Failed to fetch the OAuth token from Microsoft" | The HTTP request failed, for example after the 10 second connect timeout or the 30 second total timeout | Check the network and the DNS |
| `RuntimeException` "The token cache directory ... does not exist or is not writable" | The directory of `tokenCacheFile` is missing or read-only | Create the directory, or make it writable for the PHP user |
