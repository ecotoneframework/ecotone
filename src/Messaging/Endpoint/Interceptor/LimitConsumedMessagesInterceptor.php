<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;
use Throwable;

/**
 * Class LimitConsumedMessagesExtension
 * @package Ecotone\Messaging\Endpoint\Extension
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LimitConsumedMessagesInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;
    private bool $shouldBeStopped = false;

    private int $currentConsumedMessages = 0;

    private int $messageLimit;

    /**
     * LimitConsumedMessagesInterceptor constructor.
     * @param int $messageLimit
     */
    public function __construct(int $messageLimit)
    {
        $this->messageLimit = $messageLimit;
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->currentConsumedMessages = 0;
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
        if ($unhandledFailure) {
            $this->currentConsumedMessages--;
        }
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        $this->currentConsumedMessages++;
        $this->shouldBeStopped = $this->currentConsumedMessages >= $this->messageLimit;
    }
}
