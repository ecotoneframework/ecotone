<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
class Event
{
    private function __construct(
        private string $eventName,
        private array|object $payload,
        private array $metadata
    ) {
        Assert::notNull($payload, 'Event can not be null for ' . $eventName);
    }

    public static function create(object $event, array $metadata = [])
    {
        return new self(get_class($event), $event, $metadata);
    }

    public static function createWithType(string $eventType, array|object $event, array $metadata = [])
    {
        return new self($eventType, $event, $metadata);
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getPayload(): array|object
    {
        return $this->payload;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): self
    {
        return self::create($this->payload, $metadata);
    }

    public function withAddedMetadata(array $metadata): self
    {
        return self::createWithType($this->eventName, $this->payload, array_merge($this->metadata, $metadata));
    }
}
