# Changelog

## Version 2.0.0 - date YYYY-MM-DD

There is no 1.x release. 2.0.0 follows 0.3.

### Added

* Support for PHP 8.6
* `psr/container` as a direct requirement
* Documentation in `docs/book/v2`, with an upgrade guide from 0.x
* Tests for `XOauthTokenProviderService` and `XOauth2OutlookAuthenticator`

### Changed

* `DotMailServiceDelegator` is a plain invokable class. It works with any PSR-11 container that supports delegators
* `DotMailServiceDelegator::__invoke()` returns `Dot\Mail\Service\MailServiceInterface` and has no `$options` parameter
* `XOauthTokenProviderService` rejects empty configuration values with `InvalidArgumentException`
* Token request failures throw `RuntimeException` with the HTTP status and Microsoft's error description
* The token cache file has mode 0600 and is replaced in one step
* The token request has a connect timeout of 10 seconds and a total timeout of 30 seconds
* PHPStan level 8
* PHPUnit 12

### Deprecated

* Nothing

### Removed

* Support for PHP 8.2
* `laminas/laminas-servicemanager` as a requirement
* `DelegatorFactoryInterface` from `DotMailServiceDelegator`
* The `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory` registration in `ConfigProvider`

### Fixed

* An empty `tokenCacheFile` gave a `ValueError` after each token request
* A corrupt or incomplete token cache file stopped all mail
* The delegator required the `xoauth2_outlook` configuration for non-ESMTP transports
* A failed cache write was ignored
* The README configuration for `transport` and `tls`

## Version 0.3 - date 2025-11-03

### Added

* Support for PHP 8.5
* Tests for `ConfigProvider` and `DotMailServiceDelegator`

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* The token request sent the `Content-Type` header with `CURLOPT_HEADER`. It now uses `CURLOPT_HTTPHEADER`

## Version 0.2 - date 2025-08-19

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* `DotMailServiceDelegator` moved from `Dot\MailOutlook\Transport` to `Dot\MailOutlook`

## Version 0.1 - date 2025-08-18

Initial tagged release

### Added

* Everything

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing
