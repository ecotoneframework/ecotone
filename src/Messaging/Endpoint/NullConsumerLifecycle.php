<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Class NullConsumerLifecycle
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NullConsumerLifecycle implements ConsumerLifecycle
{
    private bool $isRunning;

    /**
     * NullConsumerLifecycle constructor.
     * @param bool $isRunning
     */
    private function __construct(bool $isRunning)
    {
        $this->isRunning = $isRunning;
    }

    public static function createRunning(): self
    {
        return new self(true);
    }

    /**
     * @return NullConsumerLifecycle
     */
    public static function createStopped(): self
    {
        return new self(false);
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->isRunning = true;
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->isRunning = false;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return 'null';
    }
}
