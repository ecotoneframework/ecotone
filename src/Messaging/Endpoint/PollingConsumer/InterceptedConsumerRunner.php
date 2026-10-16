<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\MessagePoller;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\Endpoint\Interceptor\PcntlTerminationListener;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Scheduling\CronTrigger;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class InterceptedConsumerRunner implements EndpointRunner
{
    public function __construct(
        private NonProxyGateway             $gateway,
        private MessagePoller               $messagePoller,
        private PollingMetadata             $defaultPollingMetadata,
        private EcotoneClockInterface       $clock,
        private PcntlTerminationListener    $pcntlTerminationListener,
        private LoggingGateway              $logger,
        private MessagingEntrypointService   $messagingEntrypoint,
        private ExpressionEvaluationService $expressionEvaluationService,
        private AsyncHandlerAnnotationRegistry $asyncHandlerAnnotationRegistry,
        private AsyncEndpointAnnotationContext $asyncEndpointAnnotationContext,
        private MessageBuilder $messageBuilder,
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
        $interceptors = InterceptedConsumer::createInterceptorsForPollingMetadata($pollingMetadata, $this->logger, $this->clock, $this->pcntlTerminationListener);
        $interceptedGateway = new InterceptedGateway($this->gateway, $interceptors);

        $trigger = $this->createTrigger($pollingMetadata);

        $interceptedConsumer = new ScheduledTaskConsumer(
            SyncTaskScheduler::createWithEmptyTriggerContext($this->clock, $pollingMetadata),
            $trigger,
            new PollToGatewayTaskExecutor(
                messagePoller: $this->messagePoller,
                gateway: $interceptedGateway,
                messagingEntrypoint: $this->messagingEntrypoint,
                asyncHandlerAnnotationRegistry: $this->asyncHandlerAnnotationRegistry,
                asyncEndpointAnnotationContext: $this->asyncEndpointAnnotationContext,
                messageBuilder: $this->messageBuilder,
            ),
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
                $pollingMetadata->cronExpression,
                []
            );
            return CronTrigger::createWith((string) $cronValue);
        }

        // Evaluate fixed rate expression if provided
        if ($pollingMetadata->hasFixedRateExpression()) {
            $fixedRateValue = $this->expressionEvaluationService->evaluate(
                $pollingMetadata->fixedRateExpression,
                []
            );

            return PeriodicTrigger::create((int) $fixedRateValue, $pollingMetadata->initialDelayInMilliseconds);
        }

        // Fall back to static values
        return $pollingMetadata->cron
            ? CronTrigger::createWith($pollingMetadata->cron)
            : PeriodicTrigger::create($pollingMetadata->fixedRateInMilliseconds, $pollingMetadata->initialDelayInMilliseconds);
    }
}
