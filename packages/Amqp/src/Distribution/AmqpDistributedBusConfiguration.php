<?php

namespace Ecotone\Amqp\Distribution;

use Ecotone\Modelling\DistributedBus;
use Enqueue\AmqpExt\AmqpConnectionFactory;

/**
 * Class RegisterAmqpPublisher
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpDistributedBusConfiguration
{
    private const DISTRIBUTION_TYPE_PUBLISHER = 'publisher';
    private const DISTRIBUTION_TYPE_CONSUMER = 'consumer';
    private const DISTRIBUTION_TYPE_BOTH = 'both';
    public const DEFAULT_UNIQUE_DISTRIBUTION_KEY = 'distribution_bus_';

    private string $amqpConnectionReference;
    private ?string $outputDefaultConversionMediaType;
    private string $referenceName;
    private string $headerMapper = '*';
    private bool $defaultPersistentDelivery = true;
    private string $distributionType;

    private function __construct(string $amqpConnectionReference, ?string $outputDefaultConversionMediaType, string $referenceName, string $distributionType)
    {
        $this->amqpConnectionReference = $amqpConnectionReference;
        $this->outputDefaultConversionMediaType = $outputDefaultConversionMediaType;
        $this->referenceName = $referenceName;
        $this->distributionType = $distributionType;
    }

    public static function createPublisher(string $busReferenceName = DistributedBus::class, ?string $outputDefaultConversionMediaType = null, string $amqpConnectionReference = AmqpConnectionFactory::class): self
    {
        return new self($amqpConnectionReference, $outputDefaultConversionMediaType, $busReferenceName, self::DISTRIBUTION_TYPE_PUBLISHER);
    }

    public static function createConsumer(string $amqpConnectionReference = AmqpConnectionFactory::class): self
    {
        return new self($amqpConnectionReference, null, '', self::DISTRIBUTION_TYPE_CONSUMER);
    }

    public function isPublisher(): bool
    {
        return in_array($this->distributionType, [self::DISTRIBUTION_TYPE_PUBLISHER, self::DISTRIBUTION_TYPE_BOTH]);
    }

    public function isConsumer(): bool
    {
        return in_array($this->distributionType, [self::DISTRIBUTION_TYPE_CONSUMER, self::DISTRIBUTION_TYPE_BOTH]);
    }

    /**
     * @return string
     */
    public function getAmqpConnectionReference(): string
    {
        return $this->amqpConnectionReference;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     */
    public function withHeaderMapper(string $headerMapper): static
    {
        $this->headerMapper = $headerMapper;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultPersistentDelivery(): bool
    {
        return $this->defaultPersistentDelivery;
    }

    /**
     * @param bool $defaultPersistentDelivery
     */
    public function withDefaultPersistentDelivery(bool $defaultPersistentDelivery): static
    {
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        return $this;
    }

    public function getDefaultPersistentDelivery(): bool
    {
        return $this->defaultPersistentDelivery;
    }

    /**
     * @return string
     */
    public function getHeaderMapper(): string
    {
        return $this->headerMapper;
    }

    /**
     * @return string|null
     */
    public function getOutputDefaultConversionMediaType(): ?string
    {
        return $this->outputDefaultConversionMediaType;
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}
