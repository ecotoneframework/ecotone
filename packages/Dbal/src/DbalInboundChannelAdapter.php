<?php

namespace Ecotone\Dbal;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;

class DbalInboundChannelAdapter implements TaskExecutor
{
    /**
     * @var CachedConnectionFactory
     */
    private $cachedConnectionFactory;
    /**
     * @var InboundChannelAdapterEntrypoint
     */
    private $entrypointGateway;
    /**
     * @var bool
     */
    private $declareOnStartup;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var InboundMessageConverter
     */
    private $inboundMessageConverter;
    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(CachedConnectionFactory $cachedConnectionFactory, InboundChannelAdapterEntrypoint $entrypointGateway, bool $declareOnStartup, string $queueName, int $receiveTimeoutInMilliseconds, InboundMessageConverter $inboundMessageConverter)
    {
        $this->cachedConnectionFactory = $cachedConnectionFactory;
        $this->entrypointGateway = $entrypointGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->queueName = $queueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->inboundMessageConverter = $inboundMessageConverter;
    }

    public function execute(): void
    {
        $message = $this->receiveMessage();

        if ($message) {
            $this->entrypointGateway->executeEntrypoint($message);
        }
    }

    public function receiveMessage(): ?Message
    {
        if (! $this->initialized) {
            /** @var DbalContext $context */
            $context = $this->cachedConnectionFactory->createContext();

            $context->createDataBaseTable();
            $context->createQueue($this->queueName);
            $this->initialized = true;
        }

        $consumer = $this->cachedConnectionFactory->getConsumer(new DbalDestination($this->queueName));

        /** @var DbalMessage $dbalMessage */
        $dbalMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

        if (! $dbalMessage) {
            return null;
        }

        return $this->inboundMessageConverter->toMessage($dbalMessage, $consumer)->build();
    }
}
