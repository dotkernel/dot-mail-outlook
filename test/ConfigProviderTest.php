<?php

declare(strict_types=1);

namespace DotTest\MailOutlook;

use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\ConfigProvider;
use Dot\MailOutlook\DotMailServiceDelegator;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

#[CoversClass(ConfigProvider::class)]
class ConfigProviderTest extends TestCase
{
    private array $config;

    public function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testHasDependencies(): void
    {
        $this->assertArrayHasKey('dependencies', $this->config);
    }

    public function testDependenciesHasFactories(): void
    {
        $this->assertArrayHasKey('factories', $this->config['dependencies']);
        $this->assertArrayHasKey(
            XOauthTokenProviderService::class,
            $this->config['dependencies']['factories']
        );
        $this->assertArrayHasKey(
            EsmtpTransportFactory::class,
            $this->config['dependencies']['factories']
        );
        $this->assertArrayHasKey(
            XOauth2OutlookAuthenticator::class,
            $this->config['dependencies']['factories']
        );
    }

    public function testDependenciesHasDelegators(): void
    {
        $this->assertArrayHasKey('delegators', $this->config['dependencies']);

        $this->assertSame(
            [DotMailServiceDelegator::class],
            $this->config['dependencies']['delegators']['dot-mail.service.default']
        );
    }
}
