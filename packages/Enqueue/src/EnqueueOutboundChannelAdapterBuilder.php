<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

abstract class EnqueueOutboundChannelAdapterBuilder implements MessageHandlerBuilder
{
    public const         DEFAULT_AUTO_DECLARE = true;
    public const DEFAULT_TIME_TO_LIVE = null;
    public const DEFAULT_DELIVERY_DELAY = null;
    public const DEFAULT_PRIORITY = null;

    /**
     * @var string
     */
    protected $endpointId;
    /**
     * @var string
     */
    protected $inputChannelName = '';
    protected array $headerMapper = [];
    /**
     * @var bool
     */
    protected $autoDeclare = self::DEFAULT_AUTO_DECLARE;
    /**
     * @var MediaType
     */
    protected $defaultConversionMediaType;
    /**
     * @var int|null
     */
    protected $defaultTimeToLive = self::DEFAULT_TIME_TO_LIVE;
    /**
     * @var int|null
     */
    protected $defaultDeliveryDelay = self::DEFAULT_DELIVERY_DELAY;
    /**
     * @var int|null
     */
    protected $defaultPriority = self::DEFAULT_PRIORITY;
    /**
     * @var string[]
     */
    protected $requiredReferenceNames = [];

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    public function withDefaultTimeToLive(?int $timeInMilliseconds): self
    {
        $this->defaultTimeToLive = $timeInMilliseconds;

        return $this;
    }

    public function withDefaultDeliveryDelay(?int $deliveryDelayInMilliseconds): self
    {
        $this->defaultDeliveryDelay = $deliveryDelayInMilliseconds;

        return $this;
    }

    public function withDefaultPriority(?int $priority): self
    {
        $this->defaultPriority = $priority;

        return $this;
    }

    /**
     * @param string $mediaType
     *
     * @return static
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
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
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @return static
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = explode(',', $headerMapper);

        return $this;
    }

    /**
     * @param bool $toDeclare
     *
     * @return static
     */
    public function withAutoDeclareOnSend(bool $toDeclare): self
    {
        $this->autoDeclare = $toDeclare;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId)
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferenceNames;
    }

    public function __toString()
    {
        return 'Outbound Adapter for channel ' . $this->inputChannelName;
    }

    protected function initialize(string $connectionReferenceName): void
    {
        $this->requiredReferenceNames[] = $connectionReferenceName;
    }
}
