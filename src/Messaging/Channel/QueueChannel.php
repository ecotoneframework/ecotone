<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class QueueChannel implements PollableChannel, DefinedObject
{
    /**
     * @var Message[] $queue
     */
    private array $queue = [];

    public function __construct(private string $name, private bool $batchMessagesSupport = false)
    {
    }

    public static function create(string $name = 'unknown', bool $batchMessagesSupport = false): self
    {
        return new self($name, $batchMessagesSupport);
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
        return array_shift($this->queue);
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
        // No cleanup needed for queue channels
    }

    public function __toString()
    {
        return 'in memory queue: ' . $this->name;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->name, $this->batchMessagesSupport]);
    }
}
