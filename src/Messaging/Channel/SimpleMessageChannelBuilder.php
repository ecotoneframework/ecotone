<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\PollableChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SimpleMessageChannelBuilder implements MessageChannelWithSerializationBuilder
{
    private function __construct(
        private string               $messageChannelName,
        private MessageChannel       $messageChannel,
        private bool                 $isPollable,
        private ?MediaType           $conversionMediaType,
        private HeaderMapper         $headerMapper,
        private FinalFailureStrategy $finalFailureStrategy,
        private bool                 $isAutoAcked,
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

    public static function createQueueChannel(string $messageChannelName, bool $delayable = false, string|MediaType|null $conversionMediaType = null, FinalFailureStrategy $finalFailureStrategy = FinalFailureStrategy::RESEND, bool $isAutoAcked = true): self
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

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return $this->isPollable;
    }

    public function getFinalFailureStrategy(): FinalFailureStrategy
    {
        return $this->finalFailureStrategy;
    }

    public function isAutoAcked(): bool
    {
        return $this->isAutoAcked;
    }

    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->conversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    /**
     * @inheritDoc
     */
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
        return new DefinedObjectWrapper($this->messageChannel);
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}
