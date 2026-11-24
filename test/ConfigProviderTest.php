<?php

declare(strict_types=1);

namespace DotTest\MailOutlook;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\ConfigProvider;
use Dot\MailOutlook\DotMailServiceDelegator;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

/**
 * @phpstan-import-type DependencyConfig from ConfigProvider
 */
#[CoversClass(ConfigProvider::class)]
class ConfigProviderTest extends TestCase
{
    /** @var array{dependencies: DependencyConfig} */
    private array $config;

    protected function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testHasDependencies(): void
    {
        $this->assertSame((new ConfigProvider())->getDependencies(), $this->config['dependencies']);
    }

    public function testDependenciesHasFactories(): void
    {
        $this->assertSame(
            [
                XOauthTokenProviderService::class  => AttributedServiceFactory::class,
                XOauth2OutlookAuthenticator::class => AttributedServiceFactory::class,
            ],
            $this->config['dependencies']['factories']
        );
    }

    public function testDoesNotRegisterEsmtpTransportFactory(): void
    {
        $this->assertArrayNotHasKey(EsmtpTransportFactory::class, $this->config['dependencies']['factories']);
    }

    public function testDependenciesHasDelegators(): void
    {
        $this->assertSame(
            ['dot-mail.service.default' => [DotMailServiceDelegator::class]],
            $this->config['dependencies']['delegators']
        );
    }
}
