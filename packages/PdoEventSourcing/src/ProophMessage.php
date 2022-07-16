<?php

namespace Ecotone\EventSourcing;

use DateTimeImmutable;
use Ecotone\Messaging\Support\Assert;
use Prooph\Common\Messaging\Message;
use Ramsey\Uuid\UuidInterface;

class ProophMessage implements Message
{
    private UuidInterface $messageId;
    private DateTimeImmutable $createdAt;
    private $payload;
    private array $metadata;
    private string $messageName;

    public function __construct(UuidInterface $messageId, DateTimeImmutable $createdAt, $payload, array $metadata, string $messageName)
    {
        $this->payload = $payload;
        $this->metadata = $metadata;
        $this->messageName = $messageName;
        $this->messageId = $messageId;
        $this->createdAt = $createdAt;
    }

    public function messageName(): string
    {
        return $this->messageName;
    }

    public function messageType(): string
    {
        return self::TYPE_EVENT;
    }

    public function uuid(): UuidInterface
    {
        return $this->messageId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): Message
    {
        $message = clone $this;

        $message->metadata = $metadata;

        return $message;
    }

    public function withAddedMetadata(string $key, $value): Message
    {
        Assert::notNullAndEmpty($key, 'Invalid key');

        $message = clone $this;

        $message->metadata[$key] = $value;

        return $message;
    }
}
