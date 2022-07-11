<?php

namespace Ecotone\Amqp\Configuration;

use Ecotone\Enqueue\EnqueueInboundChannelAdapterBuilder;
use Enqueue\AmqpExt\AmqpConnectionFactory;

class AmqpMessageConsumerConfiguration
{
    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var string
     * @Required()
     */
    private $queueName;
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * comma separated list of headers to be mapped. (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @var string
     */
    private $headerMapper = '';
    /**
     * @var int
     */
    protected $receiveTimeoutInMilliseconds = EnqueueInboundChannelAdapterBuilder::DEFAULT_RECEIVE_TIMEOUT;

    private function __construct(string $endpointId, string $queueName, string $amqpConnectionReferenceName)
    {
        $this->endpointId = $endpointId;
        $this->queueName = $queueName;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
    }

    public static function create(string $endpointId, string $queueName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $amqpConnectionReferenceName);
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getAmqpConnectionReferenceName(): string
    {
        return $this->amqpConnectionReferenceName;
    }

    public function getHeaderMapper(): string
    {
        return $this->headerMapper;
    }

    public function withHeaderMapper(string $headerMapper): AmqpMessageConsumerConfiguration
    {
        $self = clone $this;

        $self->headerMapper = $headerMapper;
        return $self;
    }

    public function getReceiveTimeoutInMilliseconds(): int
    {
        return $this->receiveTimeoutInMilliseconds;
    }

    public function withReceiveTimeoutInMilliseconds(int $receiveTimeoutInMilliseconds): AmqpMessageConsumerConfiguration
    {
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        return $this;
    }
}
