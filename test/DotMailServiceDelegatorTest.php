<?php

declare(strict_types=1);

namespace DotTest\MailOutlook;

use Dot\Mail\Email;
use Dot\Mail\Options\MailOptions;
use Dot\Mail\Service\LogService;
use Dot\Mail\Service\MailService;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\DotMailServiceDelegator;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

#[CoversClass(DotMailServiceDelegator::class)]
class DotMailServiceDelegatorTest extends TestCase
{
    private EsmtpTransport $esmtpTransport;
    private MailService $mailService;

    protected function setUp(): void
    {
        $logService           = $this->createMock(LogService::class);
        $emailMessage         = $this->createMock(Email::class);
        $mailOptions          = $this->createMock(MailOptions::class);
        $this->esmtpTransport = new EsmtpTransport();
        $this->mailService    = new MailService($logService, $emailMessage, $this->esmtpTransport, $mailOptions);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testWillSetXOauthOutlookAuthenticatorInDotMailServiceInstance(): void
    {
        $tokenProvider = $this->createMock(XOauthTokenProviderService::class);
        $authenticator = new XOauth2OutlookAuthenticator($tokenProvider);
        $container     = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())->method('get')->willReturn($authenticator);

        $delegatorReturn = (new DotMailServiceDelegator())(
            $container,
            '',
            fn () => $this->mailService
        );

        $this->assertInstanceOf(EsmtpTransport::class, $this->mailService->getTransport());

        $reflection         = new ReflectionClass($this->esmtpTransport);
        $reflectionProperty = $reflection->getProperty('authenticators')->getValue($this->esmtpTransport);

        $this->assertContainsOnlyInstancesOf(XOauth2OutlookAuthenticator::class, $reflectionProperty);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testWillNotSetXOauthOutlookAuthenticatorOnInvalidTransport(): void
    {
        $tokenProvider = $this->createMock(XOauthTokenProviderService::class);
        $authenticator = new XOauth2OutlookAuthenticator($tokenProvider);
        $container     = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())->method('get')->willReturn($authenticator);

        $this->mailService->setTransport(new SendmailTransport());

        $delegatorReturn = (new DotMailServiceDelegator())(
            $container,
            '',
            fn () => $this->mailService
        );

        $this->assertInstanceOf(SendmailTransport::class, $this->mailService->getTransport());

        $reflection         = new ReflectionClass($this->esmtpTransport);
        $reflectionProperty = $reflection->getProperty('authenticators')->getValue($this->esmtpTransport);

        $this->assertNotContains(XOauth2OutlookAuthenticator::class, $reflectionProperty);
    }
}
