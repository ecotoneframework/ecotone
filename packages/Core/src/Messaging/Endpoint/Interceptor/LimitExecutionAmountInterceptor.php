<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class LimitExecutionAmountInterceptor
 * @package Ecotone\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LimitExecutionAmountInterceptor implements ConsumerInterceptor
{
    private bool $shouldBeStopped = false;

    private int $currentExecutionAmount = 0;

    private int $executionLimit;

    /**
     * LimitConsumedMessagesInterceptor constructor.
     * @param int $executionLimit
     */
    public function __construct(int $executionLimit)
    {
        $this->executionLimit = $executionLimit;
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->currentExecutionAmount = 0;
        $this->shouldBeStopped = false;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function shouldBeThrown(\Throwable $exception) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return $this->shouldBeStopped;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
        $this->currentExecutionAmount++;
        $this->shouldBeStopped = $this->currentExecutionAmount >= $this->executionLimit;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {}
}