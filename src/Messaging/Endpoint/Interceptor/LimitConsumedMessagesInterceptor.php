<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class LimitConsumedMessagesExtension
 * @package Ecotone\Messaging\Endpoint\Extension
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LimitConsumedMessagesInterceptor implements ConsumerInterceptor
{
    private bool $shouldBeStopped = false;

    private int $currentSentMessages = 0;

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
        $this->currentSentMessages = 0;
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
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        $this->currentSentMessages++;

        $this->shouldBeStopped = $this->currentSentMessages >= $this->messageLimit;
    }
}