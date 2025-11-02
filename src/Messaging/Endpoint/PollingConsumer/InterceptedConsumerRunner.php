<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Scheduling\CronTrigger;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;

/**
 * licence Apache-2.0
 */
class InterceptedConsumerRunner implements EndpointRunner
{
    public function __construct(
        private NonProxyGateway            $gateway,
        private MessagePoller              $messagePoller,
        private PollingMetadata            $defaultPollingMetadata,
        private EcotoneClockInterface      $clock,
        private LoggingGateway             $logger,
        private MessagingEntrypoint        $messagingEntrypoint,
        private ExpressionEvaluationService $expressionEvaluationService,
    ) {
    }

    public function runEndpointWithExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $this->createConsumer($executionPollingMetadata)->run();
    }

    public function createConsumer(?ExecutionPollingMetadata $executionPollingMetadata): ConsumerLifecycle
    {
        $this->logger->info('Message Consumer starting to consume messages');
        $pollingMetadata = $this->defaultPollingMetadata->applyExecutionPollingMetadata($executionPollingMetadata);
        $interceptors = InterceptedConsumer::createInterceptorsForPollingMetadata($pollingMetadata, $this->logger, $this->clock);
        $interceptedGateway = new InterceptedGateway($this->gateway, $interceptors);

        $trigger = $this->createTrigger($pollingMetadata);

        $interceptedConsumer = new ScheduledTaskConsumer(
            SyncTaskScheduler::createWithEmptyTriggerContext($this->clock, $pollingMetadata),
            $trigger,
            new PollToGatewayTaskExecutor($this->messagePoller, $interceptedGateway, $this->messagingEntrypoint),
        );

        if ($interceptors) {
            return new InterceptedConsumer(
                $interceptedConsumer,
                $interceptors,
                $this->messagingEntrypoint,
                $pollingMetadata->getEndpointId(),
                $this->messagePoller
            );
        } else {
            return $interceptedConsumer;
        }
    }

    private function createTrigger(PollingMetadata $pollingMetadata)
    {
        // Evaluate cron expression if provided
        if ($pollingMetadata->hasCronExpression()) {
            $cronValue = $this->expressionEvaluationService->evaluate(
                $pollingMetadata->getCronExpression(),
                []
            );
            return CronTrigger::createWith((string) $cronValue);
        }

        // Evaluate fixed rate expression if provided
        if ($pollingMetadata->hasFixedRateExpression()) {
            $fixedRateValue = $this->expressionEvaluationService->evaluate(
                $pollingMetadata->getFixedRateExpression(),
                []
            );

            return PeriodicTrigger::create((int) $fixedRateValue, $pollingMetadata->getInitialDelayInMilliseconds());
        }

        // Fall back to static values
        return $pollingMetadata->getCron()
            ? CronTrigger::createWith($pollingMetadata->getCron())
            : PeriodicTrigger::create($pollingMetadata->getFixedRateInMilliseconds(), $pollingMetadata->getInitialDelayInMilliseconds());
    }
}
