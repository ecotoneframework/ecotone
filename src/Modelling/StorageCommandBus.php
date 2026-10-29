<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
class StorageCommandBus implements CommandBus
{
    private array $calls = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function send(object $command, array $metadata = []): mixed
    {
        $this->calls[] = [$command, $metadata];

        return null;
    }

    public function sendWithRouting(string $routingKey, mixed $command = [], string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): mixed
    {
        $this->calls[] = [$routingKey, $command, $commandMediaType, $metadata];

        return null;
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
