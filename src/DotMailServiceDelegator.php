<?php

declare(strict_types=1);

namespace Dot\MailOutlook;

use Dot\Mail\Service\MailServiceInterface;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function get_debug_type;
use function sprintf;

/**
 * Sets the Outlook XOAUTH2 authenticator on the ESMTP transport of a dot-mail service.
 *
 * Register it as a delegator of a dot-mail service in any container that supports delegators.
 */
class DotMailServiceDelegator
{
    /**
     * @param callable(): mixed $callback
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): MailServiceInterface
    {
        $mailService = $callback();
        if (! $mailService instanceof MailServiceInterface) {
            throw new RuntimeException(sprintf(
                'The service "%s" must be an instance of %s, %s given.',
                $name,
                MailServiceInterface::class,
                get_debug_type($mailService)
            ));
        }

        $transport = $mailService->getTransport();
        if (! $transport instanceof EsmtpTransport) {
            return $mailService;
        }

        $authenticator = $container->get(XOauth2OutlookAuthenticator::class);
        if (! $authenticator instanceof XOauth2OutlookAuthenticator) {
            throw new RuntimeException(sprintf(
                'The service "%s" must be an instance of %s, %s given.',
                XOauth2OutlookAuthenticator::class,
                XOauth2OutlookAuthenticator::class,
                get_debug_type($authenticator)
            ));
        }

        $transport->setAuthenticators([$authenticator]);

        return $mailService;
    }
}
