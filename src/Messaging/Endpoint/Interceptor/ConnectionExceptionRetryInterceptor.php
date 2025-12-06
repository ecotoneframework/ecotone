<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * licence Apache-2.0
 */
class ConnectionExceptionRetryInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;
    private int $currentNumberOfRetries = 0;
    private ?\Ecotone\Messaging\Handler\Recoverability\RetryTemplate $retryTemplate;

    public function __construct(private EcotoneClockInterface $clock, private LoggerInterface $logger, ?RetryTemplateBuilder $retryTemplate, private bool $isStoppedOnError)
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
    public function shouldBeThrown(Throwable $exception): bool
    {
        if (! ($exception instanceof ConnectionException) || $this->isStoppedOnError) {
            return true;
        }

        $this->currentNumberOfRetries++;
        if (! $this->retryTemplate || ! $this->retryTemplate->canBeCalledNextTime($this->currentNumberOfRetries)) {
            $this->logger->critical('Connection retry to Message Channel was exceed.', ['exception' => $exception]);
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

        $this->clock->sleep(Duration::milliseconds($this->retryTemplate->calculateNextDelay($this->currentNumberOfRetries)));
    }

    /**
     * @inheritDoc
     */
    public function postRun(?Throwable $unhandledFailure): void
    {
        if ($unhandledFailure) {
            return;
        }

        $this->currentNumberOfRetries = 0;
    }
}
