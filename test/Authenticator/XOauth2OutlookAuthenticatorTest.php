<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Authenticator;

use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use DotTest\MailOutlook\Fixture\RecordingEsmtpTransport;
use DotTest\MailOutlook\Fixture\StubbedTokenProviderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function base64_encode;
use function sys_get_temp_dir;

#[CoversClass(XOauth2OutlookAuthenticator::class)]
class XOauth2OutlookAuthenticatorTest extends TestCase
{
    public function testKeyword(): void
    {
        $this->assertSame('XOAUTH2', $this->authenticator(new StubbedTokenProviderService(
            StubbedTokenProviderService::validConfig(sys_get_temp_dir() . '/unused.json')
        ))->getAuthKeyword());
    }

    public function testSendsXoauth2Command(): void
    {
        $config        = StubbedTokenProviderService::validConfig(sys_get_temp_dir() . '/unused.json');
        $tokenProvider = new class ($config) extends StubbedTokenProviderService {
            public function getToken(): string
            {
                return 'the-token';
            }
        };
        $transport     = new RecordingEsmtpTransport();
        $transport->setUsername('sender@example.com');

        $this->authenticator($tokenProvider)->authenticate($transport);

        $this->assertSame(
            [
                [
                    'AUTH XOAUTH2 ' . base64_encode("user=sender@example.com\1auth=Bearer the-token\1\1") . "\r\n",
                    [235],
                ],
            ],
            $transport->commands
        );
    }

    private function authenticator(StubbedTokenProviderService $tokenProvider): XOauth2OutlookAuthenticator
    {
        return new XOauth2OutlookAuthenticator($tokenProvider);
    }
}
