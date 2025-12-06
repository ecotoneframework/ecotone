<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Attribute\OnConsumerStop;
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
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Psr\Log\LoggerInterface;
use Throwable;

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
    public function __construct(
        private ConsumerLifecycle $interceptedConsumer,
        private array $consumerInterceptors,
        private MessagingEntrypoint $messagingEntrypoint,
        private string $endpointId,
        private MessagePoller $messagePoller,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        foreach ($this->consumerInterceptors as $consumerInterceptor) {
            $consumerInterceptor->onStartup();
        }

        try {
            while ($this->shouldBeRunning()) {
                $exception = null;
                foreach ($this->consumerInterceptors as $consumerInterceptor) {
                    $consumerInterceptor->preRun();
                }
                try {
                    $this->interceptedConsumer->run();
                } catch (ConnectionException $exception) {
                    foreach ($this->consumerInterceptors as $consumerInterceptor) {
                        if ($consumerInterceptor->shouldBeThrown($exception)) {
                            throw $exception->getPrevious() ?? $exception;
                        }
                    }
                } catch (Throwable $exception) {
                    throw $exception;
                } finally {
                    foreach ($this->consumerInterceptors as $consumerInterceptor) {
                        $consumerInterceptor->postRun($exception);
                    }
                }
            }
        } finally {
            // @TODO Message Poller for same class may have multiple definitions, therefore onConsumer attribute wont work
            // Needed some more sophisticated way to use attributes in that case
            $this->messagePoller->onConsumerStop();

            $this->messagingEntrypoint->sendWithHeaders(
                $this->endpointId,
                [],
                OnConsumerStop::CONSUMER_STOP_CHANNEL_NAME
            );

            foreach ($this->consumerInterceptors as $consumerInterceptor) {
                $consumerInterceptor->onShutdown();
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
