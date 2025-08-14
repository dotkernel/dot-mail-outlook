<?php

declare(strict_types=1);

namespace Dot\MailOutlook\Authenticator;

use Dot\DependencyInjection\Attribute\Inject;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use JsonException;
use Symfony\Component\Mailer\Transport\Smtp\Auth\AuthenticatorInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function base64_encode;

readonly class XOauth2OutlookAuthenticator implements AuthenticatorInterface
{
    /**
     * Adapted from Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator but getting the token dynamically.
     */
    #[Inject(XOauthTokenProviderService::class)]
    public function __construct(
        private XOauthTokenProviderService $tokenProvider,
    ) {
    }

    public function getAuthKeyword(): string
    {
        return 'XOAUTH2';
    }

    /**
     * @see https://developers.google.com/google-apps/gmail/xoauth2_protocol#the_sasl_xoauth2_mechanism
     *
     * @throws JsonException
     */
    public function authenticate(EsmtpTransport $client): void
    {
        $client->executeCommand(
            'AUTH XOAUTH2 '
            . base64_encode(
                'user=' . $client->getUsername()
                . "\1auth=Bearer " . $this->tokenProvider->getToken()
                . "\1\1"
            )
            . "\r\n",
            [235]
        );
    }
}
