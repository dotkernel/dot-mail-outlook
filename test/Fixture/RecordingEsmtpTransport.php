<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Fixture;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Records the SMTP commands in place of sending them to a server.
 */
class RecordingEsmtpTransport extends EsmtpTransport
{
    /** @var list<array{string, list<int>}> */
    public array $commands = [];

    /**
     * @param list<int> $codes
     */
    public function executeCommand(string $command, array $codes): string
    {
        $this->commands[] = [$command, $codes];

        return '';
    }
}
