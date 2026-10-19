<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Api\Attribute\OnConsumerStop;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\MessagePoller;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\Interceptor\ConnectionExceptionRetryInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\FinishWhenNoMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitExecutionAmountInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\PcntlTerminationListener;
use Ecotone\Messaging\Endpoint\Interceptor\SignalInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\TimeLimitInterceptor;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
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
        private MessagingEntrypointService $messagingEntrypoint,
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
    public static function createInterceptorsForPollingMetadata(PollingMetadata $pollingMetadata, LoggerInterface $logger, EcotoneClockInterface $clock, PcntlTerminationListener $pcntlTerminationListener): array
    {
        $interceptors = [];
        if ($pollingMetadata->handledMessageLimit > 0) {
            $interceptors[] = new LimitConsumedMessagesInterceptor($pollingMetadata->handledMessageLimit);
        }
        if ($pollingMetadata->memoryLimitInMegabytes !== 0) {
            $interceptors[] = new LimitMemoryUsageInterceptor($pollingMetadata->memoryLimitInMegabytes);
        }
        if ($pollingMetadata->withSignalInterceptors) {
            $interceptors[] = new SignalInterceptor($pcntlTerminationListener);
        }
        if ($pollingMetadata->executionAmountLimit > 0) {
            $interceptors[] = new LimitExecutionAmountInterceptor($pollingMetadata->executionAmountLimit);
        }
        if ($pollingMetadata->executionTimeLimitInMilliseconds > 0) {
            $interceptors[] = new TimeLimitInterceptor($clock, $pollingMetadata->executionTimeLimitInMilliseconds);
        }
        if ($pollingMetadata->finishWhenNoMessages) {
            $interceptors[] = new FinishWhenNoMessagesInterceptor($clock);
        }
        $interceptors[] = new ConnectionExceptionRetryInterceptor($clock, $logger, $pollingMetadata->connectionRetryTemplate, $pollingMetadata->stopOnError);

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
