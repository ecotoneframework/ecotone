<?php


namespace Ecotone\Enqueue;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;

abstract class EnqueueBackendMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    protected $channelName;
    /**
     * @var int
     */
    protected $receiveTimeoutInMilliseconds = EnqueueInboundChannelAdapterBuilder::DEFAULT_RECEIVE_TIMEOUT;
    /**
     * @var MediaType|null
     */
    protected $defaultConversionMediaType;
    /**
     * @var int
     */
    protected $timeToLive = EnqueueOutboundChannelAdapterBuilder::DEFAULT_TIME_TO_LIVE;
    /**
     * @var int
     */
    protected $deliveryDelay = EnqueueOutboundChannelAdapterBuilder::DEFAULT_DELIVERY_DELAY;
    /**
     * @var string[]
     */
    protected $requiredReferences = [];

    public function withReceiveTimeout(int $timeoutInMilliseconds): self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    public function withDefaultTimeToLive(int $timeInMilliseconds): self
    {
        $this->timeToLive = $timeInMilliseconds;

        return $this;
    }

    public function withDefaultDeliveryDelay(int $timeInMilliseconds): self
    {
        $this->deliveryDelay = $timeInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return true;
    }

    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->defaultConversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    public function getDefaultConversionMediaType(): ?MediaType
    {
        return $this->defaultConversionMediaType;
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    protected function initialize(string $connectionReferenceName): void
    {
        $this->requiredReferences[] = $connectionReferenceName;
    }
}