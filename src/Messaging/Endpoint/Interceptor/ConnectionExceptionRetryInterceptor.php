<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ConnectionExceptionRetryInterceptor implements ConsumerInterceptor
{
    private int $currentNumberOfRetries = 0;
    private ?\Ecotone\Messaging\Handler\Recoverability\RetryTemplate $retryTemplate;

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
        if (!($exception instanceof ConnectionException)) {
            return true;
        }

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
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
        $this->currentNumberOfRetries = 0;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
    }
}