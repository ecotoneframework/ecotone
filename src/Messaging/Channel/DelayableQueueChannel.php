<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use DateTimeInterface;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Api\Scheduling\DatePoint;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class DelayableQueueChannel implements PollableChannel, DefinedObject
{
    /**
     * @param Message[] $queue
     */
    public function __construct(private string $name, private array $queue = [], private int|DateTimeInterface $releaseMessagesAwaitingFor = 0, private bool $batchMessagesSupport = false)
    {
    }

    public static function create(string $name, bool $batchMessagesSupport = false): self
    {
        return new self($name, batchMessagesSupport: $batchMessagesSupport);
    }

    public function enableBatchMessagesSupport(): void
    {
        $this->batchMessagesSupport = true;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage) {
            if (! $this->batchMessagesSupport) {
                throw LicensingException::create('Sending BatchMessage is available only with Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
            }

            foreach ($payload->entries as $entry) {
                $this->queue[] = MessageBuilder::withPayload($entry['payload'])
                    ->setMultipleHeaders($entry['headers'])
                    ->build();
            }

            return;
        }

        $this->queue[] = $message;
    }

    public function sendToBeginning(Message $message): void
    {
        $this->queue = array_merge([$message], $this->queue);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        $earliestDuePosition = null;
        $earliestDueAt = null;
        foreach ($this->queue as $position => $message) {
            if (! $this->isDue($message)) {
                continue;
            }

            $dueAt = $this->dueAtInMilliseconds($message);
            if ($earliestDueAt === null || $dueAt < $earliestDueAt) {
                $earliestDuePosition = $position;
                $earliestDueAt = $dueAt;
            }
        }

        if ($earliestDuePosition === null) {
            return null;
        }

        $message = array_splice($this->queue, $earliestDuePosition, 1)[0];
        if (! $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY)) {
            return $message;
        }

        return MessageBuilder::fromMessage($message)
            ->removeHeader(MessageHeaders::DELIVERY_DELAY)
            ->build();
    }

    private function isDue(Message $message): bool
    {
        return ! $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY)
            || $message->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) <= $this->getCurrentDeliveryTimeShift($message);
    }

    private function dueAtInMilliseconds(Message $message): int
    {
        $delay = $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY) ? $message->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) : 0;

        return $message->getHeaders()->getTimestamp() * 1000 + $delay;
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        return $this->receive();
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for delayable queue channels
    }

    public function releaseMessagesAwaitingFor(int|TimeSpan|DateTimeInterface $time): void
    {
        $this->releaseMessagesAwaitingFor = $time instanceof TimeSpan ? $time->toMilliseconds() : $time;
    }

    public function __toString()
    {
        return 'in memory delayable: ' . $this->name;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->name, $this->batchMessagesSupport], 'create');
    }

    public function getCurrentDeliveryTimeShift(Message $message): int
    {
        if ($this->releaseMessagesAwaitingFor instanceof DateTimeInterface) {
            $releaseAt = DatePoint::createFromInterface($this->releaseMessagesAwaitingFor);
            $messageTimestamp = DatePoint::createFromTimestamp($message->getHeaders()->getTimestamp());
            return $releaseAt->durationSince($messageTimestamp)->inMilliseconds();
        }

        return $this->releaseMessagesAwaitingFor;
    }
}
