<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\PollableChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageChannelBuilder implements MessageChannelWithSerializationBuilder
{
    private function __construct(
        private string $messageChannelName,
        private MessageChannel $messageChannel,
        private bool $isPollable,
        private ?MediaType $conversionMediaType
    ) {
    }

    public static function create(string $messageChannelName, MessageChannel $messageChannel, string|MediaType|null $conversionMediaType = null): self
    {
        return new self(
            $messageChannelName,
            $messageChannel,
            $messageChannel instanceof PollableChannel,
            $conversionMediaType ? (is_string($conversionMediaType) ? MediaType::parseMediaType($conversionMediaType) : $conversionMediaType) : null
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

    public static function createQueueChannel(string $messageChannelName, bool $delayable = false, string|MediaType|null $conversionMediaType = null): self
    {
        $messageChannel = $delayable ? DelayableQueueChannel::create($messageChannelName) : QueueChannel::create($messageChannelName);

        return self::create($messageChannelName, $messageChannel, $conversionMediaType);
    }

    public static function createNullableChannel(string $messageChannelName): self
    {
        return self::create($messageChannelName, NullableMessageChannel::create(), null);
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return $this->isPollable;
    }

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
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
        return DefaultHeaderMapper::createAllHeadersMapping();
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        return $this->messageChannel;
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}
