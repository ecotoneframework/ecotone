<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\Uuid;

class Event
{
    private string $eventType;
    private array|object $payload;
    private array $metadata;

    private function __construct(string $eventType, array|object $payload, array $metadata)
    {
        Assert::notNull($payload, "Event can not be null for " . $eventType);

        $this->eventType = $eventType;
        $this->payload = $payload;

        if (!array_key_exists(MessageHeaders::MESSAGE_ID, $metadata)) {
            $metadata[MessageHeaders::MESSAGE_ID] = Uuid::uuid4()->toString();
        }
        if (!array_key_exists(MessageHeaders::TIMESTAMP, $metadata)) {
            $metadata[MessageHeaders::TIMESTAMP] = (int)round(microtime(true));
        }

        $this->metadata = $metadata;
    }

    public static function create(object $event, array $metadata = [])
    {
        return new self(get_class($event), $event, $metadata);
    }

    public static function createWithType(string $eventType, array|object $event, array $metadata = [])
    {
        return new self($eventType, $event, $metadata);
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getPayload() : array|object
    {
        return $this->payload;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}