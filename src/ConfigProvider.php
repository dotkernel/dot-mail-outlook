<?php

declare(strict_types=1);

namespace Dot\MailOutlook;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use Dot\MailOutlook\Transport\DotMailServiceDelegator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'delegators' => [
                'dot-mail.service.default' => [
                    DotMailServiceDelegator::class,
                ],
            ],
            'factories'  => [
                XOauthTokenProviderService::class  => AttributedServiceFactory::class,
                EsmtpTransportFactory::class       => AttributedServiceFactory::class,
                XOauth2OutlookAuthenticator::class => AttributedServiceFactory::class,
            ],
            'aliases'    => [],
        ];
    }
}
