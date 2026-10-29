<?php

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\Messaging\Scheduling\TimeSpan;

/**
 * licence Apache-2.0
 */
class OutboundMessage
{
    /** @var mixed */
    private $payload;
    /** @var string[] */
    private $headers;
    /** @var string|null */
    private $contentType;
    /** @var int|null */
    private $deliveryDelay;
    /** @var int|null */
    private $timeToLive;
    /** @var int|null */
    private $priority;

    public function __construct($payload, array $headers, ?string $contentType, int|TimeSpan|null $deliveryDelay, int|TimeSpan|null $timeToLive, ?int $priority)
    {
        $this->payload = $payload;
        $this->headers = $headers;
        $this->contentType = $contentType;
        $this->deliveryDelay = $deliveryDelay instanceof TimeSpan ? $deliveryDelay->toMilliseconds() : $deliveryDelay;
        $this->timeToLive = $timeToLive instanceof TimeSpan ? $timeToLive->toMilliseconds() : $timeToLive;
        $this->priority = $priority;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
