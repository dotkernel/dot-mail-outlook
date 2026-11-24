<?php

declare(strict_types=1);

namespace DotTest\MailOutlook;

use Dot\Mail\Email;
use Dot\Mail\Options\MailOptions;
use Dot\Mail\Service\LogService;
use Dot\Mail\Service\MailService;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Dot\MailOutlook\DotMailServiceDelegator;
use DotTest\MailOutlook\Fixture\InMemoryContainer;
use DotTest\MailOutlook\Fixture\StubbedTokenProviderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

#[CoversClass(DotMailServiceDelegator::class)]
class DotMailServiceDelegatorTest extends TestCase
{
    private const SERVICE_NAME = 'dot-mail.service.default';

    private EsmtpTransport $esmtpTransport;
    private MailService $mailService;
    private XOauth2OutlookAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->esmtpTransport = new EsmtpTransport();
        $this->mailService    = new MailService(
            new LogService([]),
            new Email(),
            $this->esmtpTransport,
            new MailOptions()
        );
        $this->authenticator  = new XOauth2OutlookAuthenticator(
            new StubbedTokenProviderService(StubbedTokenProviderService::validConfig('unused.json'))
        );
    }

    public function testSetsOutlookAuthenticatorOnEsmtpTransport(): void
    {
        $container = new InMemoryContainer([XOauth2OutlookAuthenticator::class => $this->authenticator]);

        $result = (new DotMailServiceDelegator())($container, self::SERVICE_NAME, fn () => $this->mailService);

        $this->assertSame($this->esmtpTransport, $result->getTransport());
        $this->assertSame([$this->authenticator], $this->authenticatorsOf($this->esmtpTransport));
    }

    public function testReturnsSameService(): void
    {
        $container = new InMemoryContainer([XOauth2OutlookAuthenticator::class => $this->authenticator]);

        $result = (new DotMailServiceDelegator())($container, self::SERVICE_NAME, fn () => $this->mailService);

        $this->assertSame($this->mailService, $result);
    }

    public function testSendmailTransportDoesNotNeedAuthenticator(): void
    {
        $sendmailTransport = new SendmailTransport();
        $this->mailService->setTransport($sendmailTransport);

        $result = (new DotMailServiceDelegator())(
            new InMemoryContainer(),
            self::SERVICE_NAME,
            fn () => $this->mailService
        );

        $this->assertSame($this->mailService, $result);
        $this->assertSame($sendmailTransport, $result->getTransport());
    }

    public function testThrowsWhenCallbackDoesNotReturnMailService(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The service "dot-mail.service.default" must be an instance of');

        (new DotMailServiceDelegator())(new InMemoryContainer(), self::SERVICE_NAME, fn () => new stdClass());
    }

    public function testThrowsWhenAuthenticatorHasWrongType(): void
    {
        $container = new InMemoryContainer([XOauth2OutlookAuthenticator::class => new stdClass()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stdClass given');

        (new DotMailServiceDelegator())($container, self::SERVICE_NAME, fn () => $this->mailService);
    }

    /**
     * @return array<mixed>
     */
    private function authenticatorsOf(EsmtpTransport $transport): array
    {
        $authenticators = (new ReflectionProperty(EsmtpTransport::class, 'authenticators'))->getValue($transport);
        $this->assertIsArray($authenticators);

        return $authenticators;
    }
}
