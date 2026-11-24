<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Fixture;

use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * Minimal PSR-11 container that returns the services given to the constructor.
 */
class InMemoryContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     */
    public function __construct(private array $services = [])
    {
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw ServiceNotFoundException::forId($id);
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
