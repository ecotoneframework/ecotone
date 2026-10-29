<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use DateTimeInterface;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Scheduling\DatePoint;
use Ecotone\Messaging\Scheduling\TimeSpan;
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
                throw LicensingException::create('Sending BatchMessage is available only with Ecotone Enterprise licence.');
            }

            foreach ($payload->getEntries() as $entry) {
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
        $message = array_shift($this->queue);

        if ($message !== null && $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY)) {
            if ($message->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) > $this->getCurrentDeliveryTimeShift($message)) {
                $nextAvailableMessage = $this->receive();
                array_unshift($this->queue, $message);

                if ($nextAvailableMessage === null) {
                    return null;
                }

                $message = $nextAvailableMessage;
            }

            return MessageBuilder::fromMessage($message)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build();
        }

        return $message;
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
