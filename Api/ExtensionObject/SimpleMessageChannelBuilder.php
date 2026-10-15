<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\InMemoryMessageChannelHolder;
use Ecotone\Messaging\Channel\InMemoryStreamingChannel;
use Ecotone\Messaging\Channel\MessageChannelWithSerializationBuilder;
use Ecotone\Messaging\Channel\PublishSubscribeChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\NullableMessageChannel;

/**
 * licence Apache-2.0
 */
class SimpleMessageChannelBuilder implements MessageChannelWithSerializationBuilder
{
    private function __construct(
        private string                        $messageChannelName,
        private MessageChannel                $messageChannel,
        private bool                          $isPollable,
        private ?MediaType                    $conversionMediaType,
        private HeaderMapper                  $headerMapper,
        private FinalFailureStrategy          $finalFailureStrategy,
        public readonly bool $isAutoAcked,
        private bool                          $isStreamingChannel = false,
        private ?InMemoryMessageChannelHolder $inMemoryMessageChannelHolder = null,
        private ?string                       $messageGroupId = null,
    ) {
    }

    public static function create(string $messageChannelName, MessageChannel $messageChannel, string|MediaType|null $conversionMediaType = null, FinalFailureStrategy $finalFailureStrategy = FinalFailureStrategy::RESEND, bool $isAutoAcked = true): self
    {
        return new self(
            $messageChannelName,
            $messageChannel,
            $messageChannel instanceof PollableChannel,
            $conversionMediaType ? (is_string($conversionMediaType) ? MediaType::parseMediaType($conversionMediaType) : $conversionMediaType) : null,
            DefaultHeaderMapper::createAllHeadersMapping(),
            $finalFailureStrategy,
            $isAutoAcked,
        );
    }

    public static function createDirectMessageChannel(string $messageChannelName): self
    {
        return self::create($messageChannelName, DirectChannel::create($messageChannelName), null);
    }

    public static function createPublishSubscribeChannel(string $messageChannelName): self
    {
        return self::create($messageChannelName, PublishSubscribeChannel::create($messageChannelName), null);
    }

    public static function createQueueChannel(string $messageChannelName, bool $delayable = true, string|MediaType|null $conversionMediaType = null, FinalFailureStrategy $finalFailureStrategy = FinalFailureStrategy::RESEND, bool $isAutoAcked = true): self
    {
        $messageChannel = $delayable ? DelayableQueueChannel::create($messageChannelName) : QueueChannel::create($messageChannelName);

        return self::create($messageChannelName, $messageChannel, $conversionMediaType, $finalFailureStrategy, $isAutoAcked);
    }

    public static function createNullableChannel(string $messageChannelName): self
    {
        return self::create($messageChannelName, NullableMessageChannel::create(), null);
    }

    public static function createExceptionChannel(ExceptionalQueueChannel $exceptionalQueueChannel): self
    {
        return self::create($exceptionalQueueChannel->getMessageChannelName(), $exceptionalQueueChannel);
    }

    public static function createStreamingChannel(string $messageChannelName, ?string $messageGroupId = null, string|MediaType|null $conversionMediaType = null, FinalFailureStrategy $finalFailureStrategy = FinalFailureStrategy::RELEASE, bool $isAutoAcked = true): self
    {
        $messageChannel = QueueChannel::create($messageChannelName);

        $instance = self::create($messageChannelName, $messageChannel, $conversionMediaType, $finalFailureStrategy, $isAutoAcked);
        $instance->isStreamingChannel = true;
        $instance->inMemoryMessageChannelHolder = new InMemoryMessageChannelHolder();
        $instance->messageGroupId = $messageGroupId ?? $messageChannelName;
        return $instance;
    }

    public function isPollable(): bool
    {
        return $this->isPollable;
    }

    public function isDirect(): bool
    {
        return $this->messageChannel instanceof DirectChannel;
    }

    public function isStreamingChannel(): bool
    {
        return $this->isStreamingChannel;
    }

    public function getFinalFailureStrategy(): FinalFailureStrategy
    {
        return $this->finalFailureStrategy;
    }

    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->conversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    public function getMessageChannelName(): string
    {
        return $this->messageChannelName;
    }

    public function getConversionMediaType(): ?MediaType
    {
        return $this->conversionMediaType;
    }

    public function getHeaderMapper(): HeaderMapper
    {
        return $this->headerMapper;
    }

    public function withHeaderMapping(string $headerMapper): self
    {
        $mapping = explode(',', $headerMapper);
        $this->headerMapper = DefaultHeaderMapper::createWith($mapping, $mapping);

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        if ($this->isStreamingChannel) {
            return new Definition(InMemoryStreamingChannel::class, [
                $this->messageChannelName,
                new DefinedObjectWrapper($this->inMemoryMessageChannelHolder),
                new Reference(ConsumerPositionTracker::class),
                $this->finalFailureStrategy,
                $this->isAutoAcked,
            ]);
        }

        if (
            ($this->messageChannel instanceof QueueChannel || $this->messageChannel instanceof DelayableQueueChannel)
            && $builder->getServiceConfiguration()->isRunningForEnterprise()
        ) {
            $this->messageChannel->enableBatchMessagesSupport();
        }

        return new DefinedObjectWrapper($this->messageChannel);
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}
