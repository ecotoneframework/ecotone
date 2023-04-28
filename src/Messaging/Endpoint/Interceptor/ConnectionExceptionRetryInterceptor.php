<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Psr\Log\LoggerInterface;
use Throwable;

class ConnectionExceptionRetryInterceptor implements ConsumerInterceptor
{
    private int $currentNumberOfRetries = 0;
    private ?\Ecotone\Messaging\Handler\Recoverability\RetryTemplate $retryTemplate;

    public function __construct(private LoggerInterface $logger, ?RetryTemplateBuilder $retryTemplate, private bool $isStoppedOnError)
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
    public function shouldBeThrown(Throwable $exception): bool
    {
        if (! ($exception instanceof ConnectionException) || $this->isStoppedOnError) {
            return true;
        }

        $this->currentNumberOfRetries++;
        if (! $this->retryTemplate || ! $this->retryTemplate->canBeCalledNextTime($this->currentNumberOfRetries)) {
            $this->logger->critical("Connection retry to Message Channel was exceed.", ['exception' => $exception]);
            return true;
        }

        $retryConnectionIn = $this->retryTemplate->calculateNextDelay($this->currentNumberOfRetries);
        $this->logger->info(
            ConnectionException::connectionRetryMessage($this->currentNumberOfRetries, $retryConnectionIn),
            ['exception' => $exception]
        );
        return false;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        if (! $this->retryTemplate) {
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
    public function postSend(MethodInvocation $methodInvocation): mixed
    {
        return $methodInvocation->proceed();
    }

    public function isInterestedInPostSend(): bool
    {
        return false;
    }
}
