<?php


namespace Ecotone\Dbal\Configuration;


use Ecotone\Dbal\DbalOutboundChannelAdapterBuilder;
use Enqueue\Dbal\DbalConnectionFactory;

class DbalMessagePublisherConfiguration
{
    /**
     * @var string
     */
    private $connectionReference;
    /**
     * @var string|null
     */
    private $outputDefaultConversionMediaType;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var bool
     */
    private $autoDeclareQueueOnSend = DbalOutboundChannelAdapterBuilder::DEFAULT_AUTO_DECLARE;
    /**
     * @var string
     */
    private $headerMapper = "";
    /**
     * @var string
     */
    private $queueName;

    private function __construct(string $connectionReference, string $queueName, ?string $outputDefaultConversionMediaType, string $referenceName)
    {
        $this->connectionReference = $connectionReference;
        $this->queueName = $queueName;
        $this->outputDefaultConversionMediaType = $outputDefaultConversionMediaType;
        $this->referenceName = $referenceName;
    }

    public static function create(string $publisherReferenceName, string $queueName, ?string $outputDefaultConversionMediaType = null, string $connectionReference = DbalConnectionFactory::class): self
    {
        return new self($connectionReference, $queueName, $outputDefaultConversionMediaType, $publisherReferenceName);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getConnectionReference(): string
    {
        return $this->connectionReference;
    }

    /**
     * @param bool $autoDeclareQueueOnSend
     * @return DbalMessagePublisherConfiguration
     */
    public function withAutoDeclareQueueOnSend(bool $autoDeclareQueueOnSend): DbalMessagePublisherConfiguration
    {
        $this->autoDeclareQueueOnSend = $autoDeclareQueueOnSend;

        return $this;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @return DbalMessagePublisherConfiguration
     */
    public function withHeaderMapper(string $headerMapper) : DbalMessagePublisherConfiguration
    {
        $this->headerMapper = $headerMapper;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoDeclareQueueOnSend(): bool
    {
        return $this->autoDeclareQueueOnSend;
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