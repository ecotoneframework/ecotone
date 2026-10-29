<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
class StorageEventBus implements EventBus
{
    private array $calls = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function publish(object $event, array $metadata = []): void
    {
        $this->calls[] = [$event, $metadata];
    }

    public function publishWithRouting(string $routingKey, mixed $event = [], string $eventMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): void
    {
        $this->calls[] = [$routingKey, $event, $eventMediaType, $metadata];
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
