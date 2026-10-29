<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\PollableChannel;
use RuntimeException;

/**
 * licence Apache-2.0
 */
class ExceptionalQueueChannel implements PollableChannel, MessageChannelWithSerializationBuilder, DefinedObject
{
    private int $exceptionCount = 0;
    private QueueChannel $queueChannel;

    public function __construct(private string $channelName, private bool $exceptionOnReceive, private bool $exceptionOnSend, private int $stopFailingAfterAttempt)
    {
        $this->queueChannel = QueueChannel::create();
    }

    public static function createWithExceptionOnReceive(string $channelName = 'exceptionalChannel', int $stopFailingAfterAttempt = 100): self
    {
        return new self($channelName, true, false, $stopFailingAfterAttempt);
    }

    public static function createWithExceptionOnSend(string $channelName = 'exceptionalChannel', int $stopFailingAfterAttempt = 100): self
    {
        return new self($channelName, false, true, $stopFailingAfterAttempt);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        if ($this->exceptionOnSend && $this->exceptionCount < $this->stopFailingAfterAttempt) {
            $this->exceptionCount++;
            throw new RuntimeException('Exception on send');
        }

        $this->queueChannel->send($message);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        if ($this->exceptionOnReceive && $this->exceptionCount < $this->stopFailingAfterAttempt) {
            $this->exceptionCount++;
            throw new ConnectionException();
        }

        return $this->queueChannel->receive();
    }

    public function getConversionMediaType(): ?MediaType
    {
        return null;
    }

    public function getHeaderMapper(): HeaderMapper
    {
        return DefaultHeaderMapper::createAllHeadersMapping();
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
        // No cleanup needed for exceptional queue channels
    }

    public function getExceptionCount(): int
    {
        return $this->exceptionCount;
    }

    public function getMessageChannelName(): string
    {
        return $this->channelName;
    }

    public function isPollable(): bool
    {
        return true;
    }

    public function isStreamingChannel(): bool
    {
        return false;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return $this->getDefinition();
    }

    public function getDefinition(): Definition
    {
        return new Definition(ExceptionalQueueChannel::class, [
            $this->channelName,
            $this->exceptionOnReceive,
            $this->exceptionOnSend,
            $this->stopFailingAfterAttempt,
        ]);
    }
}
