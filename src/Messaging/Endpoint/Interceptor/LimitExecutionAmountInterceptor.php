<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Endpoint\Interceptor;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class LimitExecutionAmountInterceptor
 * @package SimplyCodedSoftware\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LimitExecutionAmountInterceptor implements ConsumerInterceptor
{
    /**
     * @var bool
     */
    private $shouldBeStopped = false;

    /**
     * @var int
     */
    private $currentExecutionAmount = 0;

    /**
     * @var int
     */
    private $executionLimit;

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
        return;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        return;
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