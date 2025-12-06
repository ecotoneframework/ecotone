<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;
use Throwable;

/**
 * Class LimitExecutionAmountInterceptor
 * @package Ecotone\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LimitExecutionAmountInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;

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
    public function shouldBeStopped(): bool
    {
        return $this->shouldBeStopped;
    }

    /**
     * @inheritDoc
     */
    public function postRun(?Throwable $unhandledFailure): void
    {
        $this->currentExecutionAmount++;
        $this->shouldBeStopped = $this->currentExecutionAmount >= $this->executionLimit;
    }
}
