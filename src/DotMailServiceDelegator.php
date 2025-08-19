<?php

declare(strict_types=1);

namespace Dot\MailOutlook;

use Dot\Mail\Service\MailService as DotMailService;
use Dot\MailOutlook\Authenticator\XOauth2OutlookAuthenticator;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function assert;

class DotMailServiceDelegator implements DelegatorFactoryInterface
{
    /**
     * @param string $name
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null
    ): DotMailService {
        /** @var DotMailService $mailService */
        $mailService = $callback();

        $outlookAuthenticator = $container->get(XOauth2OutlookAuthenticator::class);

        assert($outlookAuthenticator instanceof XOauth2OutlookAuthenticator);

        $esmtpTransport = $mailService->getTransport();

        if ($esmtpTransport instanceof EsmtpTransport) {
            $esmtpTransport->setAuthenticators([$outlookAuthenticator]);
        }

        return $mailService;
    }
}
