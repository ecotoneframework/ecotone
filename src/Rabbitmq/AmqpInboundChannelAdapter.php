<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;

/**
 * Class AmqpInboundChannelAdapter
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements ConsumerLifecycle
{



    /**
     * @inheritDoc
     */
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        // TODO: Implement stop() method.
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        // TODO: Implement isRunningInSeparateThread() method.
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        // TODO: Implement getComponentName() method.
    }
}