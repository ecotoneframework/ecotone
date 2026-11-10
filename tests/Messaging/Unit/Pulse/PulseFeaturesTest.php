<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Pulse;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\DelayedRetry;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\Attribute\Poller;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Test\LicenceTesting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class PulseFeaturesTest extends TestCase
{
    public function test_without_database_transactions_the_instant_retry_defaults_leave_asynchronous_endpoints_and_the_command_bus_alone(): void
    {
        $features = $this->coreFeatures($this->orders());

        $asynchronous = $features['Instant retries on asynchronous endpoints'];
        self::assertFalse($asynchronous['enabled']);
        self::assertSame([], $asynchronous['settings']);
        self::assertStringContainsString('Not configured, and asynchronous endpoints run without a database transaction, so the default retries only asynchronous projections', $asynchronous['hint']);
        self::assertSame('InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(isEnabled: true, retryTimes: 3, retryExceptions: [])', $asynchronous['configuration']);
        $commandBus = $features['Instant retries on the command bus'];
        self::assertFalse($commandBus['enabled']);
        self::assertStringContainsString('Not configured, and the command bus runs without a database transaction', $commandBus['hint']);
        self::assertSame('InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(isEnabled: true, retryTimes: 3, retryExceptions: [])', $commandBus['configuration']);
    }

    public function test_a_failing_command_runs_once_when_the_command_bus_retries_are_reported_off_and_four_times_when_reported_on_with_three_retry_times(): void
    {
        $handlers = new class () {
            public int $attempts = 0;

            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
                $this->attempts++;
                throw new RuntimeException('Payment gateway is down');
            }
        };

        [$withoutRetries, $reportedOff] = $this->reportedBy($handlers);
        try {
            $withoutRetries->sendCommandWithRouting('order.place', 'order-1');
        } catch (RuntimeException) {
        }
        $attemptsWithoutRetries = $handlers->attempts;
        [$withRetries, $reportedOn] = $this->reportedBy($handlers, [InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true)]);
        try {
            $withRetries->sendCommandWithRouting('order.place', 'order-1');
        } catch (RuntimeException) {
        }

        self::assertFalse($reportedOff['Instant retries on the command bus']['enabled']);
        self::assertSame(1, $attemptsWithoutRetries);
        self::assertSame('3', $reportedOn['Instant retries on the command bus']['settings']['Retry times']);
        self::assertSame(5, $handlers->attempts);
    }

    public function test_instant_retries_turned_on_retry_an_asynchronous_endpoint_three_more_times_on_any_exception(): void
    {
        $features = $this->coreFeatures($this->orders(), [InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true)]);

        $asynchronous = $features['Instant retries on asynchronous endpoints'];
        self::assertTrue($asynchronous['enabled']);
        self::assertSame(['Retry times' => '3', 'Retried on' => 'any exception'], $asynchronous['settings']);
        self::assertStringContainsString('handled once and, when that fails, tried again right away up to 3 more times (4 attempts in total) on any exception', $asynchronous['hint']);
    }

    public function test_configured_instant_retries_report_their_count_and_exceptions_and_the_endpoints_overriding_them(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $orderId): void
            {
            }
        };

        $features = $this->coreFeatures($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            InstantRetryConfiguration::createWithDefaults()
                ->withCommandBusRetry(true, 2, [RuntimeException::class])
                ->withAsynchronousEndpointsRetry(false),
        ], [PulseInstantRetryCommandBus::class]);

        $commandBus = $features['Instant retries on the command bus'];
        self::assertTrue($commandBus['enabled']);
        self::assertSame(['Retry times' => '2', 'Retried on' => 'RuntimeException'], $commandBus['settings']);
        self::assertSame(['PulseInstantRetryCommandBus: 5 retry times on InvalidArgumentException'], $commandBus['handlers']);
        self::assertStringContainsString('up to 2 more times (3 attempts in total) on RuntimeException', $commandBus['hint']);
        self::assertFalse($features['Instant retries on asynchronous endpoints']['enabled']);
        self::assertStringContainsString('with no immediate second attempt', $features['Instant retries on asynchronous endpoints']['hint']);
    }

    public function test_without_a_default_error_channel_error_handling_is_reported_as_disabled_with_the_call_that_enables_it(): void
    {
        $features = $this->coreFeatures($this->orders());

        $errorChannel = $features['Error channel and delayed retries'];
        self::assertFalse($errorChannel['enabled']);
        self::assertSame('not set', $errorChannel['settings']['Default error channel']);
        self::assertSame('3 retries: after 1000 ms, 10000 ms and 100000 ms, then dead letter channel dbal_dead_letter', $errorChannel['settings']['Retries on errorChannel']);
        self::assertStringContainsString('is not sent to an error channel', $errorChannel['hint']);
        self::assertSame("ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel')", $errorChannel['configuration']);
    }

    public function test_a_default_error_channel_and_delayed_retry_handlers_are_reported_with_their_retry_schedule(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders', asynchronousExecution: [new DelayedRetry(initialDelayInMilliseconds: 500, multiplier: 2, maxRetries: 2)])]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $orderId): void
            {
            }
        };

        $features = $this->coreFeatures($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ErrorHandlerConfiguration::create('order_errors', RetryTemplateBuilder::fixedBackOff(200)->maxRetries(2)),
        ], serviceConfiguration: ServiceConfiguration::createWithDefaults()->withServiceName('order_service')->withDefaultErrorChannel('order_errors'));

        $errorChannel = $features['Error channel and delayed retries'];
        self::assertTrue($errorChannel['enabled']);
        self::assertSame('order_errors', $errorChannel['settings']['Default error channel']);
        self::assertSame('2 retries: after 200 ms and 200 ms, then no further retry', $errorChannel['settings']['Retries on order_errors']);
        self::assertSame(['order.place.endpoint: 2 retries: after 500 ms and 1000 ms, then no further retry'], $errorChannel['handlers']);
        self::assertStringContainsString("is sent to error channel 'order_errors'", $errorChannel['hint']);
    }

    public function test_endpoints_are_listed_under_their_retries_by_the_id_they_were_given_and_gateways_by_their_name(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders', asynchronousExecution: [new DelayedRetry(initialDelayInMilliseconds: 500, multiplier: 2, maxRetries: 2)])]
            #[CommandHandler('order.place', endpointId: 'Orders\\place')]
            public function place(string $orderId): void
            {
            }

            #[InstantRetry(retryTimes: 2)]
            #[Scheduled('order.expire', '2')]
            public function expire(): ?string
            {
                return null;
            }

            #[InstantRetry(retryTimes: 3)]
            #[Scheduled('order.archive', 'Orders\\archive')]
            public function archive(): ?string
            {
                return null;
            }
        };

        $features = $this->coreFeatures($handlers, [SimpleMessageChannelBuilder::createQueueChannel('orders')], [PulseDelayedRetryCommandBus::class]);

        self::assertSame(['Orders\\place: 2 retries: after 500 ms and 1000 ms, then no further retry', 'PulseDelayedRetryCommandBus: 1 retry: after 100 ms, then dead letter channel order_dead_letters'], $features['Error channel and delayed retries']['handlers']);
        self::assertSame(['2: 2 retry times on any exception', 'Orders\\archive: 3 retry times on any exception'], $features['Instant retries on asynchronous endpoints']['handlers']);
    }

    public function test_each_pollable_channel_reports_what_happens_to_a_message_that_finally_fails(): void
    {
        $failure = $this->coreFeatures($this->orders(), [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('payments', finalFailureStrategy: FinalFailureStrategy::STOP),
        ])['Final failure strategy'];

        self::assertTrue($failure['enabled']);
        self::assertSame('resend', $failure['settings']['Channel orders']);
        self::assertSame('stop', $failure['settings']['Channel payments']);
        self::assertStringContainsString('an in-memory streaming or RabbitMQ stream channel to release', $failure['hint']);
        self::assertStringContainsString('resend: the message is sent back to its channel, so the consumer goes on with the next messages', $failure['hint']);
        self::assertStringContainsString('stop: the consumer stops and the message stays in the channel', $failure['hint']);
        self::assertSame("->withFinalFailureStrategy(FinalFailureStrategy::STOP) on the channel's builder", $failure['configuration']);
    }

    public function test_resilient_sending_by_default_collects_messages_and_retries_a_failed_send_twice(): void
    {
        $sending = $this->coreFeatures($this->orders(), [SimpleMessageChannelBuilder::createQueueChannel('orders')])['Resilient sending'];

        self::assertTrue($sending['enabled']);
        self::assertSame(['Message collector' => 'on', 'Sending retries' => '2 retries: after 1 ms and 20 ms', 'Error channel for failed sends' => 'none'], $sending['settings']);
        self::assertStringContainsString('collected and sent only once the handler that produced them has finished', $sending['hint']);
        self::assertStringContainsString('a broker channel receives them just before the commit', $sending['hint']);
        self::assertSame('GlobalPollableChannelConfiguration::createWithDefaults()->withCollector(true)', $sending['configuration']);
    }

    public function test_retry_schedules_are_described_without_a_limit_beyond_five_retries_capped_by_a_max_delay_and_never(): void
    {
        $sending = $this->coreFeatures($this->orders(), [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('instant'),
            SimpleMessageChannelBuilder::createQueueChannel('many'),
            SimpleMessageChannelBuilder::createQueueChannel('capped'),
            SimpleMessageChannelBuilder::createQueueChannel('never'),
            GlobalPollableChannelConfiguration::create(RetryTemplateBuilder::exponentialBackOff(10, 2)->build()),
            PollableChannelConfiguration::create('instant', RetryTemplateBuilder::fixedBackOff(0)->build()),
            PollableChannelConfiguration::create('many', RetryTemplateBuilder::exponentialBackOff(10, 2)->maxRetries(7)->build()),
            PollableChannelConfiguration::create('capped', RetryTemplateBuilder::exponentialBackOffWithMaxDelay(10, 2, 50)->build()),
            PollableChannelConfiguration::neverRetry('never'),
        ])['Resilient sending'];

        self::assertSame('retries without a limit: after 10 ms, 20 ms, 40 ms, 80 ms, 160 ms, each next delay 2 times longer', $sending['settings']['Sending retries']);
        self::assertSame('collector on, retries without a limit: after 0 ms, 0 ms, 0 ms, 0 ms, 0 ms, failed sends to nowhere', $sending['settings']['Channel instant']);
        self::assertSame('collector on, 7 retries: after 10 ms, 20 ms, 40 ms, 80 ms and 160 ms, …, failed sends to nowhere', $sending['settings']['Channel many']);
        self::assertSame('collector on, 3 retries: after 10 ms, 20 ms and 40 ms, failed sends to nowhere', $sending['settings']['Channel capped']);
        self::assertSame('collector on, no retry, failed sends to nowhere', $sending['settings']['Channel never']);
    }

    public function test_resilient_sending_reports_a_channel_that_overrides_the_global_configuration(): void
    {
        $sending = $this->coreFeatures($this->orders(), [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            GlobalPollableChannelConfiguration::createWithDefaults()->withCollector(false),
            PollableChannelConfiguration::neverRetry('notifications')->withErrorChannel('notification_errors'),
        ])['Resilient sending'];

        self::assertSame('off', $sending['settings']['Message collector']);
        self::assertSame('collector on, no retry, failed sends to notification_errors', $sending['settings']['Channel notifications']);
        self::assertStringContainsString('sent at once', $sending['hint']);
    }

    public function test_a_consumer_lists_only_the_handlers_it_executes_and_their_error_channel_and_the_global_delayed_retries(): void
    {
        $handlers = new class () {
            #[Asynchronous(['outbox', 'payments'], asynchronousExecution: [new ErrorChannel('payment_errors')])]
            #[CommandHandler('order.pay', endpointId: 'order.pay.endpoint')]
            public function pay(string $orderId): void
            {
            }
        };

        $consumers = array_column($this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('outbox'),
            SimpleMessageChannelBuilder::createQueueChannel('payments'),
            SimpleMessageChannelBuilder::createQueueChannel('payment_errors'),
            SimpleMessageChannelBuilder::createQueueChannel('order_errors'),
            ErrorHandlerConfiguration::create('order_errors', RetryTemplateBuilder::fixedBackOff(200)->maxRetries(2)),
        ], serviceConfiguration: ServiceConfiguration::createWithDefaults()->withServiceName('order_service')->withDefaultErrorChannel('order_errors'))['consumers'], null, 'name');

        self::assertSame([], $consumers['outbox']['handlers']);
        self::assertSame(['order.pay.endpoint'], $consumers['payments']['handlers']);
        self::assertContains(['name' => 'Error channel', 'value' => 'payment_errors', 'scope' => 'consumer-level config', 'handler' => 'order.pay.endpoint'], $consumers['payments']['features']);
        self::assertContains(['name' => 'Error channel', 'value' => 'order_errors', 'scope' => 'global', 'handler' => null], $consumers['payments']['features']);
        self::assertContains(['name' => 'Delayed retries', 'value' => '2 retries: after 200 ms and 200 ms, then no further retry', 'scope' => 'global', 'handler' => null], $consumers['payments']['features']);
        self::assertContains(['name' => 'Delayed retries', 'value' => 'none', 'scope' => 'consumer-level config', 'handler' => 'order.pay.endpoint'], $consumers['payments']['features']);
    }

    public function test_a_scheduled_consumer_follows_its_poller_over_a_service_context_polling_metadata_and_reports_its_error_channel(): void
    {
        $handlers = new class () {
            #[Scheduled('report.send', 'reports')]
            #[Poller(errorChannelName: 'report_errors', fixedRateExpression: "'5000'")]
            public function report(): ?string
            {
                return null;
            }
        };

        $snapshot = $this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('report_errors'),
            PollingMetadata::create('reports')->withFixedRateInMilliseconds(60000),
        ]);
        $reports = array_column($snapshot['consumers'], null, 'name')['reports']['features'];

        self::assertContains(['name' => 'Poller', 'value' => "rate expression '5000'", 'scope' => 'consumer-level config', 'handler' => null], $reports);
        self::assertContains(['name' => 'Error channel', 'value' => 'report_errors', 'scope' => 'consumer-level config', 'handler' => null], $reports);
        self::assertContains(['name' => 'Delayed retries', 'value' => 'none', 'scope' => 'consumer-level config', 'handler' => null], $reports);
        self::assertSame(["reports: rate expression '5000'"], array_column(array_column($snapshot['packages'], 'features', 'name')['core'], 'handlers', 'name')['Scheduled endpoints']);
    }

    public function test_a_consumer_that_stops_on_error_or_has_its_error_channel_disabled_shows_it(): void
    {
        $handlers = new class () {
            #[Scheduled('report.send', 'reports')]
            public function report(): ?string
            {
                return null;
            }

            #[Scheduled('stock.sync', 'stock')]
            public function sync(): ?string
            {
                return null;
            }
        };

        $consumers = array_column($this->snapshotOf($handlers, [
            PollingMetadata::create('reports')->withStopOnError(true),
            PollingMetadata::create('stock')->withEnabledErrorChannel(false),
        ])['consumers'], 'features', 'name');

        self::assertContains(['name' => 'Error channel', 'value' => 'off, the consumer stops on the first error', 'scope' => 'consumer-level config', 'handler' => null], $consumers['reports']);
        self::assertContains(['name' => 'Final failure strategy', 'value' => 'stop, as the consumer stops on error', 'scope' => 'consumer-level config', 'handler' => null], $consumers['reports']);
        self::assertContains(['name' => 'Error channel', 'value' => 'off', 'scope' => 'consumer-level config', 'handler' => null], $consumers['stock']);
        self::assertContains(['name' => 'Delayed retries', 'value' => 'none', 'scope' => 'consumer-level config', 'handler' => null], $consumers['stock']);
        self::assertNotContains('Poller', array_column($consumers['reports'], 'name'));
        self::assertNotContains('Poller', array_column($consumers['stock'], 'name'));
    }

    public function test_a_channel_consumer_that_stops_on_error_shows_stop_instead_of_its_channel_strategy(): void
    {
        $handlers = new class () {
            #[Asynchronous('payments')]
            #[CommandHandler('order.pay', endpointId: 'order.pay.endpoint')]
            public function pay(string $orderId): void
            {
            }
        };

        $features = array_column($this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('payments', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            PollingMetadata::create('payments')->withStopOnError(true),
        ])['consumers'], 'features', 'name')['payments'];

        self::assertSame(['stop, as the consumer stops on error'], array_values(array_map(static fn (array $feature): string => $feature['value'], array_filter($features, static fn (array $feature): bool => $feature['name'] === 'Final failure strategy'))));
    }

    public function test_a_consumer_error_channel_replaces_the_default_error_channel(): void
    {
        $handlers = new class () {
            #[Asynchronous('payments')]
            #[CommandHandler('order.pay', endpointId: 'order.pay.endpoint')]
            public function pay(string $orderId): void
            {
            }
        };

        $features = array_column($this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('payments'),
            PollingMetadata::create('payments')->withErrorChannelName('payment_errors'),
            SimpleMessageChannelBuilder::createQueueChannel('payment_errors'),
        ], serviceConfiguration: ServiceConfiguration::createWithDefaults()->withServiceName('order_service')->withDefaultErrorChannel('order_errors'))['consumers'], 'features', 'name')['payments'];

        self::assertSame([['name' => 'Error channel', 'value' => 'payment_errors', 'scope' => 'consumer-level config', 'handler' => null]], array_values(array_filter($features, static fn (array $feature): bool => $feature['name'] === 'Error channel')));
    }

    public function test_a_streaming_channel_left_at_its_release_default_is_tagged_global_and_a_queue_set_to_stop_is_its_own(): void
    {
        $handlers = new class () {
            #[Asynchronous('events')]
            #[EventHandler('order.placed', endpointId: 'order.placed.endpoint')]
            public function whenPlaced(string $orderId): void
            {
            }

            #[Asynchronous('payments')]
            #[CommandHandler('order.pay', endpointId: 'order.pay.endpoint')]
            public function pay(string $orderId): void
            {
            }
        };

        $consumers = array_column($this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createStreamingChannel('events'),
            SimpleMessageChannelBuilder::createQueueChannel('payments', finalFailureStrategy: FinalFailureStrategy::STOP),
        ])['consumers'], null, 'name');

        self::assertContains(['name' => 'Final failure strategy', 'value' => 'release', 'scope' => 'global', 'handler' => null], $consumers['events']['features']);
        self::assertContains(['name' => 'Final failure strategy', 'value' => 'stop', 'scope' => 'consumer-level config', 'handler' => null], $consumers['payments']['features']);
    }

    public function test_combined_channels_report_the_channels_a_message_passes_through_and_the_handlers_using_them(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $orderId): void
            {
            }

            #[Asynchronous(['outbox', 'payments'])]
            #[CommandHandler('order.pay', endpointId: 'order.pay.endpoint')]
            public function pay(string $orderId): void
            {
            }
        };

        $outbox = $this->coreFeatures($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('outbox'),
            SimpleMessageChannelBuilder::createQueueChannel('payments'),
            CombinedMessageChannel::create('orders', ['outbox', 'payments']),
        ])['Combined channels (outbox)'];

        self::assertTrue($outbox['enabled']);
        self::assertSame(['Channel orders' => 'outbox, then payments'], $outbox['settings']);
        self::assertSame(['order.place.endpoint: orders', 'order.pay.endpoint: outbox, then payments'], $outbox['handlers']);
        self::assertStringContainsString('goes through each of its channels in turn', $outbox['hint']);
    }

    public function test_a_service_without_combined_channels_reports_the_outbox_off(): void
    {
        $outbox = $this->coreFeatures($this->orders())['Combined channels (outbox)'];

        self::assertFalse($outbox['enabled']);
        self::assertSame("CombinedMessageChannel::create('orders', ['outbox', 'orders_broker'])", $outbox['configuration']);
    }

    public function test_scheduled_endpoints_are_listed_with_how_often_they_run(): void
    {
        $handlers = new class () {
            #[Scheduled('order.expire', 'order.expire.endpoint')]
            #[Poller(fixedRateInMilliseconds: 60000)]
            public function expire(): ?string
            {
                return null;
            }

            #[Scheduled('report.send', 'reports')]
            #[Poller(cron: '0 6 * * *')]
            public function report(): ?string
            {
                return null;
            }

            #[Scheduled('cache.warm', 'cache.warm.endpoint')]
            public function warm(): ?string
            {
                return null;
            }

            #[Scheduled('stock.sync', 'stock.sync.endpoint')]
            #[Poller(cronExpression: "'*/5 * * * *'")]
            public function sync(): ?string
            {
                return null;
            }
        };

        $scheduled = $this->coreFeatures($handlers, [PollingMetadata::create('cache.warm.endpoint')->withFixedRateInMilliseconds(30000)])['Scheduled endpoints'];

        self::assertTrue($scheduled['enabled']);
        self::assertSame(['Scheduled endpoints' => '4'], $scheduled['settings']);
        self::assertSame(['order.expire.endpoint: every 1 minute', 'reports: cron 0 6 * * *', 'cache.warm.endpoint: every 30 seconds', "stock.sync.endpoint: cron expression '*/5 * * * *'"], $scheduled['handlers']);
        self::assertStringContainsString('runs on its own schedule', $scheduled['hint']);
    }

    public function test_the_service_configuration_reports_serialization_memory_limit_connection_retries_and_logged_headers(): void
    {
        $service = $this->coreFeatures($this->orders(), serviceConfiguration: ServiceConfiguration::createWithDefaults()
            ->withServiceName('order_service')
            ->withConsumerMemoryLimit(256)
            ->withHeadersInLogContext(['tenant']))['Service configuration'];

        self::assertTrue($service['enabled']);
        self::assertSame([
            'Default serialization' => 'application/x-php-serialized',
            'Consumer memory limit' => '256 MB',
            'Connection retries' => '3 retries: after 100 ms, 300 ms and 900 ms',
            'Headers in log context' => 'tenant',
        ], $service['settings']);
        self::assertStringContainsString('stops after the message it is handling once it uses more than 256 MB', $service['hint']);
    }

    public function test_distributed_handlers_and_orchestrators_are_listed_under_their_feature(): void
    {
        $handlers = new class () {
            #[Distributed]
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }

            #[Distributed]
            #[EventHandler('billing.invoiced')]
            public function whenInvoiced(string $invoiceId): void
            {
            }

            #[Orchestrator(inputChannelName: 'order.fulfil')]
            public function fulfil(): array
            {
                return ['order.reserve'];
            }

            #[InternalHandler('order.reserve')]
            public function reserve(string $orderId): void
            {
            }
        };

        $features = $this->coreFeatures($handlers);

        self::assertTrue($features['Distributed Bus']['enabled']);
        self::assertSame(['order.place → place', 'billing.invoiced → whenInvoiced'], $features['Distributed Bus']['handlers']);
        self::assertTrue($features['Orchestrators']['enabled']);
        self::assertSame(['order.fulfil → fulfil'], $features['Orchestrators']['handlers']);
    }

    public function test_a_service_without_distributed_handlers_or_orchestrators_reports_both_as_disabled(): void
    {
        $features = $this->coreFeatures($this->orders());

        self::assertFalse($features['Distributed Bus']['enabled']);
        self::assertSame([], $features['Distributed Bus']['handlers']);
        self::assertFalse($features['Orchestrators']['enabled']);
    }

    public function test_without_its_configuration_the_dynamic_consistency_boundary_is_reported_off_with_the_call_that_enables_it(): void
    {
        $boundary = $this->coreFeatures($this->orders())['Dynamic consistency boundary'];

        self::assertFalse($boundary['enabled']);
        self::assertSame([], $boundary['settings']);
        self::assertStringContainsString('#[EventTag] is ignored', $boundary['hint']);
        self::assertSame('DynamicConsistencyBoundaryConfiguration::createWithDefaults()', $boundary['configuration']);
    }

    public function test_an_enabled_dynamic_consistency_boundary_reports_its_tags_snapshots_and_the_decisions_taken_in_it(): void
    {
        $handlers = new class () {
            #[DecisionBoundary]
            public static function boundary(PulseEnrollStudent $command): EventCriteria
            {
                return EventCriteria::tag('course', $command->courseId);
            }

            #[CommandHandler]
            public function enroll(PulseEnrollStudent $command, PulseCourseSeats $seats): array
            {
                return [new PulseStudentEnrolled($command->courseId)];
            }
        };

        $boundary = $this->coreFeatures($handlers, [
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tenant'])->withSnapshotsFor(PulseCourseSeats::class, thresholdTrigger: 50),
        ], [PulseCourseSeats::class, PulseStudentEnrolled::class])['Dynamic consistency boundary'];

        self::assertTrue($boundary['enabled']);
        self::assertSame(['Filter-only tags' => 'tenant', 'Snapshotted decision models' => 'PulseCourseSeats every 50 events', 'Aggregates without a counter tag' => 'none'], $boundary['settings']);
        self::assertSame(['decision model PulseCourseSeats', 'decision boundary class@anonymous::boundary'], $boundary['handlers']);
        self::assertStringContainsString('appended with the tags of their #[EventTag] properties', $boundary['hint']);
        self::assertStringContainsString('its append is refused with DecisionModelConcurrencyException', $boundary['hint']);
    }

    public function test_each_consumer_lists_its_features_tagged_as_its_own_configuration_or_the_global_one(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders', asynchronousExecution: [new DelayedRetry(initialDelayInMilliseconds: 500, multiplier: 2, maxRetries: 2)])]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $orderId): void
            {
            }

            #[Asynchronous('orders')]
            #[CommandHandler('order.cancel', endpointId: 'order.cancel.endpoint')]
            public function cancel(string $orderId): void
            {
            }

            #[InstantRetry(retryTimes: 2)]
            #[Scheduled('report.send', 'reports')]
            #[Poller(fixedRateInMilliseconds: 60000)]
            public function report(): ?string
            {
                return null;
            }
        };

        $consumers = array_column($this->snapshotOf($handlers, [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 1),
        ])['consumers'], null, 'name');

        self::assertSame('polling', $consumers['orders']['kind']);
        self::assertSame(['order.place.endpoint', 'order.cancel.endpoint'], $consumers['orders']['handlers']);
        $orders = array_column($consumers['orders']['features'], null, 'name');
        self::assertSame(['name' => 'Instant retries', 'value' => '1 retry on any exception', 'scope' => 'global', 'handler' => null], $orders['Instant retries']);
        self::assertSame(['name' => 'Error channel', 'value' => 'none', 'scope' => 'global', 'handler' => null], $orders['Error channel']);
        self::assertSame(['name' => 'Final failure strategy', 'value' => 'resend', 'scope' => 'global', 'handler' => null], $orders['Final failure strategy']);
        self::assertSame(['name' => 'Delayed retries', 'value' => '2 retries: after 500 ms and 1000 ms, then no further retry', 'scope' => 'consumer-level config', 'handler' => 'order.place.endpoint'], $orders['Delayed retries']);
        self::assertSame('inbound', $consumers['reports']['kind']);
        $reports = array_column($consumers['reports']['features'], null, 'name');
        self::assertSame(['name' => 'Instant retries', 'value' => '2 retries on any exception', 'scope' => 'consumer-level config', 'handler' => null], $reports['Instant retries']);
        self::assertSame(['name' => 'Poller', 'value' => 'every 1 minute', 'scope' => 'consumer-level config', 'handler' => null], $reports['Poller']);
    }

    /**
     * @param object[] $extensionObjects
     * @param string[] $classesToResolve
     * @return array<string, array<string, mixed>>
     */
    private function coreFeatures(object $handlers, array $extensionObjects = [], array $classesToResolve = [], ?ServiceConfiguration $serviceConfiguration = null): array
    {
        return $this->reportedBy($handlers, $extensionObjects, $classesToResolve, $serviceConfiguration)[1];
    }

    /**
     * @param object[] $extensionObjects
     * @return array<string, mixed>
     */
    private function snapshotOf(object $handlers, array $extensionObjects = [], ?ServiceConfiguration $serviceConfiguration = null): array
    {
        return $this->reportedBy($handlers, $extensionObjects, serviceConfiguration: $serviceConfiguration)[2];
    }

    /**
     * @param object[] $extensionObjects
     * @param string[] $classesToResolve
     * @return array{0: FlowTestSupport, 1: array<string, array<string, mixed>>, 2: array<string, mixed>}
     */
    private function reportedBy(object $handlers, array $extensionObjects = [], array $classesToResolve = [], ?ServiceConfiguration $serviceConfiguration = null): array
    {
        $pulse = new class () {
            public array $snapshots = [];

            #[InternalHandler('ecotone.pulse.snapshot_received')]
            public function received(string $snapshot): void
            {
                $this->snapshots[] = $snapshot;
            }
        };
        $channels = [SimpleMessageChannelBuilder::createQueueChannel('order_channel'), SimpleMessageChannelBuilder::createQueueChannel('pulse_channel')];
        $orderService = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class, ...$classesToResolve],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [
                ...$channels,
                ...$extensionObjects,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ecotone_pulse', channelName: 'pulse_channel'),
                $serviceConfiguration ?? ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                PulseConfiguration::createWithDefaults('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $pulseService = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$pulse::class],
            containerOrAvailableServices: [$pulse],
            extensionObjects: [
                ...$channels,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $snapshot = json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR);
        $packages = array_column($snapshot['packages'], 'features', 'name');

        return [$orderService, array_column($packages['core'], null, 'name'), $snapshot];
    }

    private function orders(): object
    {
        return new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }
        };
    }
}

#[InstantRetry(retryTimes: 5, exceptions: [InvalidArgumentException::class])]
interface PulseInstantRetryCommandBus extends CommandBus
{
}

#[DelayedRetry(initialDelayInMilliseconds: 100, maxRetries: 1, deadLetterChannel: 'order_dead_letters')]
interface PulseDelayedRetryCommandBus extends CommandBus
{
}

final readonly class PulseEnrollStudent
{
    public function __construct(public string $courseId, public string $tenantId = 'acme')
    {
    }
}

final readonly class PulseStudentEnrolled
{
    public function __construct(#[EventTag('course')] public string $courseId, #[EventTag('tenant')] public string $tenantId = 'acme')
    {
    }
}

#[DecisionModel]
final class PulseCourseSeats
{
    #[Apply]
    public function enrolled(PulseStudentEnrolled $event): void
    {
    }
}
