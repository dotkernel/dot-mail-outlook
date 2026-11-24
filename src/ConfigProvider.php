<?php

declare(strict_types=1);

namespace Dot\MailOutlook;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\Service\XOauthTokenProviderService;

/**
 * @phpstan-type DependencyConfig array{
 *     delegators: array<string, list<class-string>>,
 *     factories: array<class-string, class-string>
 * }
 */
class ConfigProvider
{
    /**
     * @return array{dependencies: DependencyConfig}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return DependencyConfig
     */
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
                XOauth2OutlookAuthenticator::class => AttributedServiceFactory::class,
            ],
        ];
    }
}
