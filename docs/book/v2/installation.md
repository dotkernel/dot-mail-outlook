# Installation

## Requirements

- PHP 8.3, 8.4, 8.5 or 8.6
- The `curl` extension
- `dotkernel/dot-mail` 5.x
- A PSR-11 container that supports factories and delegators, for example Laminas Service Manager

If your application uses Laminas Service Manager, your application must require it.
This package does not require it.

## Install the package

Run this Composer command:

```shell
composer require dotkernel/dot-mail-outlook
```

## Register the ConfigProvider

Add this line to `config/config.php`:

```php
\Dot\MailOutlook\ConfigProvider::class,
```

Put it after `\Dot\Mail\ConfigProvider::class`.

Then configure the package. Read [Configuration](configuration.md).
