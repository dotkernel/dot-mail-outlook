# Upgrading from 0.x

Version 2.0.0 follows 0.3. There is no 1.x release.

## Steps

1. Make sure that the application runs on PHP 8.3 or newer.
2. If the application uses Laminas Service Manager, make sure that its `composer.json` requires `laminas/laminas-servicemanager`.
3. Run `composer require dotkernel/dot-mail-outlook:^2.0`.
4. Set each `xoauth2_outlook` value. `tokenCacheFile` must point to a file in a writable directory.
5. Correct the dot-mail options as described in [Configuration](configuration.md#dot-mail-configuration).
6. Send a test email.

## Changes that can break code

| Change | Symptom | Fix |
| --- | --- | --- |
| PHP 8.2 is not supported | Composer does not install 2.x on PHP 8.2 | Use PHP 8.3 or newer |
| `laminas/laminas-servicemanager` is not a requirement | Class `Laminas\ServiceManager\...` not found, if the application got it only through this package | Require `laminas/laminas-servicemanager` in the application |
| `DotMailServiceDelegator` does not implement `DelegatorFactoryInterface` | `instanceof DelegatorFactoryInterface` is false | Register the delegator by class name, as before. Check the class, not the interface |
| `DotMailServiceDelegator::__invoke()` has the signature `(ContainerInterface $container, string $name, callable $callback): MailServiceInterface` | A subclass with the 0.x signature still works. A subclass that declares a return type wider than `MailServiceInterface`, for example `object`, gives a fatal "Declaration must be compatible" error | Declare `MailService` or `MailServiceInterface` as the return type |
| `DotMailServiceDelegator::__invoke()` returns `MailServiceInterface` | Static analysis reports calls to `MailService` methods that are not in the interface | Use the methods of `Dot\Mail\Service\MailServiceInterface`, or check the type |
| `EsmtpTransportFactory` is not registered | "Service not found" for `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory`. In 0.x, the service could not be created either | Register your own factory for it, if you use it |
| Empty `xoauth2_outlook` values are rejected | `InvalidArgumentException` when the container creates the token provider | Fill in each value. Set `tokenCacheFile` to a writable path |
| Token request failures throw only `RuntimeException` | Code that catches `JsonException` from `getToken()` does not catch the error | Catch `RuntimeException` |
| An unwritable cache path throws | `RuntimeException`, where 0.x gave a warning and sent the email | Make the directory of `tokenCacheFile` writable |
| The cache file has mode 0600 | Another user cannot read the cache file | Run all workers as the same user, or give each user its own cache file |
| `XOauthTokenProviderService` has new protected methods | A subclass with a method of the same name and a different signature gives a fatal error | Rename the method in the subclass |

The cache file format did not change. 2.x reads cache files that 0.x wrote.

New protected methods in `XOauthTokenProviderService`: `readCachedToken()`, `requestToken()`, `writeCache()`, `configValue()` and `describeError()`.

## Replacing DelegatorFactoryInterface

In 0.x, `DotMailServiceDelegator` implemented `Laminas\ServiceManager\Factory\DelegatorFactoryInterface`.
In 2.x, it is a plain invokable class. Laminas Service Manager accepts it in the `delegators` configuration as before.

A subclass with the 0.x signature still loads, because PHP accepts a parameter type that is wider and an extra optional parameter.
We recommend the new signature for new code.

Before, a subclass had the Laminas signature:

```php
use Dot\Mail\Service\MailService;
use Dot\MailOutlook\DotMailServiceDelegator;
use Psr\Container\ContainerInterface;

class MyDelegator extends DotMailServiceDelegator
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null
    ): MailService {
        $mailService = parent::__invoke($container, $name, $callback, $options);
        // ...
        return $mailService;
    }
}
```

After, a subclass has the plain signature:

```php
use Dot\Mail\Service\MailServiceInterface;
use Dot\MailOutlook\DotMailServiceDelegator;
use Psr\Container\ContainerInterface;

class MyDelegator extends DotMailServiceDelegator
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): MailServiceInterface
    {
        $mailService = parent::__invoke($container, $name, $callback);
        // ...
        return $mailService;
    }
}
```

Code that checked the interface must check the class instead:

```php
// Before
if ($delegator instanceof \Laminas\ServiceManager\Factory\DelegatorFactoryInterface) {
    // ...
}

// After
if ($delegator instanceof \Dot\MailOutlook\DotMailServiceDelegator) {
    // ...
}
```
