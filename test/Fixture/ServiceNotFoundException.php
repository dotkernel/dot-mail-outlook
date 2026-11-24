<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Fixture;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(sprintf('The service "%s" is not in the container.', $id));
    }
}
