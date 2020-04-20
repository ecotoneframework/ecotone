<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplate;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplateBuilder;

class ChannelPollRetryInterceptor implements ConsumerInterceptor
{
    /**
     * @var int
     */
    private $currentNumberOfRetries = 0;
    /**
     * @var RetryTemplate|null
     */
    private $retryTemplate;

    public function __construct(?RetryTemplateBuilder $retryTemplate)
    {
        $this->retryTemplate = $retryTemplate ? $retryTemplate->build() : null;
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->currentNumberOfRetries = 0;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeThrown(\Throwable $exception): bool
    {
        $this->currentNumberOfRetries++;
        if (!$this->retryTemplate || !$this->retryTemplate->canBeCalledNextTime($this->currentNumberOfRetries)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        if (!$this->retryTemplate) {
            return;
        }

        usleep($this->retryTemplate->calculateNextDelay($this->currentNumberOfRetries) * 1000);
        return;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        return;
    }
}