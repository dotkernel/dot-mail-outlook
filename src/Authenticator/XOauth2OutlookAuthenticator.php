<?php

declare(strict_types=1);

namespace Dot\MailOutlook\Authenticator;

use Dot\DependencyInjection\Attribute\Inject;
use Dot\MailOutlook\Service\XOauthTokenProviderService;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\Auth\AuthenticatorInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function base64_encode;

/**
 * SASL XOAUTH2 authenticator that gets a new token from XOauthTokenProviderService when it is necessary.
 *
 * Adapted from Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator.
 */
readonly class XOauth2OutlookAuthenticator implements AuthenticatorInterface
{
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
     * @see https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth
     *
     * @throws RuntimeException
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
