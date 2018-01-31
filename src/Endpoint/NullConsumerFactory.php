<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

/**
 * Class NullConsumerFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NullConsumerFactory implements ConsumerLifecycle
{
    /**
     * @inheritDoc
     */
    public function start(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return "";
    }
}