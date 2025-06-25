<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\Interceptor\ConnectionExceptionRetryInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\FinishWhenNoMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitExecutionAmountInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\SignalInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\TimeLimitInterceptor;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ContinuouslyRunningConsumer
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InterceptedConsumer implements ConsumerLifecycle
{
    private bool $shouldBeRunning = true;

    /**
     * @param ConsumerLifecycle $interceptedConsumer
     * @param ConsumerInterceptor[] $consumerInterceptors
     */
    public function __construct(private ConsumerLifecycle $interceptedConsumer, private array $consumerInterceptors)
    {
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        foreach ($this->consumerInterceptors as $consumerInterceptor) {
            $consumerInterceptor->onStartup();
        }

        while ($this->shouldBeRunning()) {
            foreach ($this->consumerInterceptors as $consumerInterceptor) {
                $consumerInterceptor->preRun();
            }
            $runResultedInConnectionException = false;
            try {
                $this->interceptedConsumer->run();
            } catch (ConnectionException $exception) {
                $runResultedInConnectionException = true;
                foreach ($this->consumerInterceptors as $consumerInterceptor) {
                    if ($consumerInterceptor->shouldBeThrown($exception)) {
                        throw $exception->getPrevious() ?? $exception;
                    }
                }
            }
            if (! $runResultedInConnectionException) {
                foreach ($this->consumerInterceptors as $consumerInterceptor) {
                    $consumerInterceptor->postRun();
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->shouldBeRunning = false;
    }

    /**
     * @return ConsumerInterceptor[]
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createInterceptorsForPollingMetadata(PollingMetadata $pollingMetadata, LoggerInterface $logger, EcotoneClockInterface $clock): array
    {
        $interceptors = [];
        if ($pollingMetadata->getHandledMessageLimit() > 0) {
            $interceptors[] = new LimitConsumedMessagesInterceptor($pollingMetadata->getHandledMessageLimit());
        }
        if ($pollingMetadata->getMemoryLimitInMegabytes() !== 0) {
            $interceptors[] = new LimitMemoryUsageInterceptor($pollingMetadata->getMemoryLimitInMegabytes());
        }
        if ($pollingMetadata->isWithSignalInterceptors()) {
            $interceptors[] = new SignalInterceptor();
        }
        if ($pollingMetadata->getExecutionAmountLimit() > 0) {
            $interceptors[] = new LimitExecutionAmountInterceptor($pollingMetadata->getExecutionAmountLimit());
        }
        if ($pollingMetadata->getExecutionTimeLimitInMilliseconds() > 0) {
            $interceptors[] = new TimeLimitInterceptor($clock, $pollingMetadata->getExecutionTimeLimitInMilliseconds());
        }
        if ($pollingMetadata->finishWhenNoMessages()) {
            $interceptors[] = new FinishWhenNoMessagesInterceptor($clock);
        }
        $interceptors[] = new ConnectionExceptionRetryInterceptor($clock, $logger, $pollingMetadata->getConnectionRetryTemplate(), $pollingMetadata->isStoppedOnError());

        return $interceptors;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return $this->interceptedConsumer->isRunningInSeparateThread();
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->interceptedConsumer->getConsumerName();
    }

    /**
     * @return bool
     */
    private function shouldBeRunning(): bool
    {
        if (! $this->shouldBeRunning) {
            return false;
        }

        foreach ($this->consumerInterceptors as $consumerInterceptor) {
            if ($consumerInterceptor->shouldBeStopped()) {
                return false;
            }
        }

        return true;
    }
}
