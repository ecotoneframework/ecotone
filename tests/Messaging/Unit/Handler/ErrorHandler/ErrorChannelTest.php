<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Test\LicenceTesting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel\OrderService;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ErrorChannelTest extends TestCase
{
    public function test_exception_handling_with_retries_without_dead_letter_uses_final_failure_strategy(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            (ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel']))->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::RESEND, delayable: false)),
            pathToRootCatalog: __DIR__ . '/../../../../'
        );

        $ecotone
            ->sendCommandWithRouting('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        $this->assertSame(3, $ecotone->sendQueryWithRouting('getCallCount'));

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;
        $this->assertSame(1, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }


    public function test_exception_handling_with_retries_without_dead_letter_uses_final_failure_strategy_with_ignore(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            (ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel']))->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::IGNORE, delayable: false)),
            pathToRootCatalog: __DIR__ . '/../../../../'
        );

        $ecotone
            ->sendCommandWithRouting('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;
        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }

    public function test_using_custom_channel_for_error_handling(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            (ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    ErrorHandlerConfiguration::create(
                        $errorChannelName = 'failureOrders',
                        RetryTemplateBuilder::exponentialBackOff(1, 1)
                            ->maxRetries(2)
                    ),
                ])
                ->withDefaultErrorChannel($errorChannelName))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::IGNORE, delayable: false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel($errorChannelName, finalFailureStrategy: FinalFailureStrategy::RESEND, delayable: false)),
            pathToRootCatalog: __DIR__ . '/../../../../'
        );

        $ecotone
            ->sendCommandWithRouting('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false))
        ;

        $this->assertSame(3, $ecotone->sendQueryWithRouting('getCallCount'));

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(stopOnError: true))
        ;
        $this->assertSame(1, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }

    public function test_inbound_channel_adapter_sends_failed_message_to_default_error_channel_using_routing_slip(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [FailingScheduledExample::class],
            [new FailingScheduledExample()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withDefaultErrorChannel('customErrorChannel')
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel('customErrorChannel', delayable: false),
                ]),
        );

        $ecotone->run(FailingScheduledExample::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));

        /** @var PollableChannel $errorChannel */
        $errorChannel = $ecotone->getMessageChannel('customErrorChannel');
        $errorMessage = $errorChannel->receive();
        $this->assertNotNull($errorMessage, 'Expected failed message to be delivered to default error channel');

        $headers = $errorMessage->getHeaders();
        $this->assertFalse(
            $headers->containsKey(MessageHeaders::POLLED_CHANNEL_NAME),
            'Inbound Channel Adapter has no source pollable Message Channel; POLLED_CHANNEL_NAME must not be set'
        );
        $this->assertTrue(
            $headers->containsKey(MessageHeaders::ROUTING_SLIP),
            'Routing slip is required for replay back to an Inbound Channel Adapter consumer (Kafka, AMQP inbound, #[Scheduled], etc.)'
        );
        $this->assertSame(FailingScheduledExample::REQUEST_CHANNEL, $headers->get(MessageHeaders::ROUTING_SLIP));
    }

    public function test_inbound_channel_adapter_with_delayed_retry_template_throws_clear_error_about_missing_polled_channel(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [FailingScheduledExample::class],
            [new FailingScheduledExample()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withDefaultErrorChannel('retryErrorChannel')
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    ErrorHandlerConfiguration::create(
                        'retryErrorChannel',
                        RetryTemplateBuilder::exponentialBackOff(1, 1)->maxRetries(2)
                    ),
                ]),
        );

        $this->expectException(\Ecotone\Messaging\Handler\MessageHandlingException::class);
        $this->expectExceptionMessage('does not contain information about origination channel from which it was polled');

        $ecotone->run(FailingScheduledExample::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));
    }

    public function test_async_handler_routes_failure_to_error_channel_declared_via_endpoint_annotations(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [AsyncFailingHandler::class],
            [new AsyncFailingHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::ERROR_CHANNEL_A, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::ERROR_CHANNEL_B, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(AsyncFailingHandler::ROUTING_KEY_A, 'payload-a');
        $ecotone->run(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));

        /** @var PollableChannel $errorChannelA */
        $errorChannelA = $ecotone->getMessageChannel(AsyncFailingHandler::ERROR_CHANNEL_A);
        /** @var PollableChannel $errorChannelB */
        $errorChannelB = $ecotone->getMessageChannel(AsyncFailingHandler::ERROR_CHANNEL_B);

        $this->assertNotNull($errorChannelA->receive(), 'Handler A failure must be routed to its declared error channel');
        $this->assertNull($errorChannelB->receive(), 'Handler B error channel must remain empty when only handler A failed');
    }

    public function test_two_async_handlers_sharing_channel_each_route_failures_to_their_own_error_channel(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [AsyncFailingHandler::class],
            [new AsyncFailingHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::ERROR_CHANNEL_A, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::ERROR_CHANNEL_B, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(AsyncFailingHandler::ROUTING_KEY_A, 'payload-a');
        $ecotone->sendCommandWithRouting(AsyncFailingHandler::ROUTING_KEY_B, 'payload-b');

        $ecotone->run(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 2,
            stopOnError: false,
        ));

        /** @var PollableChannel $errorChannelA */
        $errorChannelA = $ecotone->getMessageChannel(AsyncFailingHandler::ERROR_CHANNEL_A);
        /** @var PollableChannel $errorChannelB */
        $errorChannelB = $ecotone->getMessageChannel(AsyncFailingHandler::ERROR_CHANNEL_B);

        $messageInA = $errorChannelA->receive();
        $messageInB = $errorChannelB->receive();

        $this->assertNotNull($messageInA, 'Handler A failure must land in error channel A');
        $this->assertNotNull($messageInB, 'Handler B failure must land in error channel B');
        $this->assertNull($errorChannelA->receive(), 'Only one message expected in error channel A');
        $this->assertNull($errorChannelB->receive(), 'Only one message expected in error channel B');
    }

    public function test_async_handler_endpoint_annotation_error_channel_overrides_default_error_channel(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [AsyncFailingHandler::class],
            [new AsyncFailingHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withDefaultErrorChannel('globalDefaultErrorChannel')
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncFailingHandler::ERROR_CHANNEL_A, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel('globalDefaultErrorChannel', delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(AsyncFailingHandler::ROUTING_KEY_A, 'payload-a');
        $ecotone->run(AsyncFailingHandler::SHARED_ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));

        /** @var PollableChannel $globalDefault */
        $globalDefault = $ecotone->getMessageChannel('globalDefaultErrorChannel');
        /** @var PollableChannel $errorChannelA */
        $errorChannelA = $ecotone->getMessageChannel(AsyncFailingHandler::ERROR_CHANNEL_A);

        $this->assertNotNull($errorChannelA->receive(), 'Per-handler #[ErrorChannel] must override the default error channel');
        $this->assertNull($globalDefault->receive(), 'Default error channel must not receive the failure when handler declares its own');
    }

    public function test_retry_policy_retries_handler_until_success(): void
    {
        $handler = new DelayedRetryHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [DelayedRetryHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(DelayedRetryHandler::ASYNC_CHANNEL, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(DelayedRetryHandler::ROUTING_KEY_RECOVERS, 'payload');

        $ecotone->run(DelayedRetryHandler::ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));
        $this->assertSame(1, $ecotone->sendQueryWithRouting('retryHandler.attemptsRecovers'));
        $this->assertFalse($ecotone->sendQueryWithRouting('retryHandler.finallyHandled'));

        $ecotone->run(DelayedRetryHandler::ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));
        $this->assertSame(2, $ecotone->sendQueryWithRouting('retryHandler.attemptsRecovers'));
        $this->assertTrue($ecotone->sendQueryWithRouting('retryHandler.finallyHandled'));
    }

    public function test_retry_policy_routes_to_dead_letter_when_retries_exhausted(): void
    {
        $handler = new DelayedRetryHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [DelayedRetryHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(DelayedRetryHandler::ASYNC_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(DelayedRetryHandler::DEAD_LETTER_CHANNEL, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(DelayedRetryHandler::ROUTING_KEY_DEAD_LETTER, 'payload');

        for ($i = 0; $i < 3; $i++) {
            $ecotone->run(DelayedRetryHandler::ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
                handledMessageLimit: 1,
                stopOnError: false,
            ));
        }

        $this->assertSame(3, $ecotone->sendQueryWithRouting('retryHandler.attemptsDeadLetter'), 'Handler invoked maxRetries+1 times before exhaustion');

        /** @var PollableChannel $deadLetter */
        $deadLetter = $ecotone->getMessageChannel(DelayedRetryHandler::DEAD_LETTER_CHANNEL);
        $this->assertNotNull($deadLetter->receive(), 'Failed message must land in the dead letter channel after retries are exhausted');
        $this->assertNull($deadLetter->receive(), 'Only one failed message expected in the dead letter channel');
    }

    public function test_retry_policy_overrides_global_default_error_channel(): void
    {
        $handler = new DelayedRetryHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [DelayedRetryHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withDefaultErrorChannel('globalDefaultErrorChannel')
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(DelayedRetryHandler::ASYNC_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel(DelayedRetryHandler::DEAD_LETTER_CHANNEL, delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel('globalDefaultErrorChannel', delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting(DelayedRetryHandler::ROUTING_KEY_OVERRIDE, 'payload');

        for ($i = 0; $i < 2; $i++) {
            $ecotone->run(DelayedRetryHandler::ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(
                handledMessageLimit: 1,
                stopOnError: false,
            ));
        }

        $this->assertSame(2, $ecotone->sendQueryWithRouting('retryHandler.attemptsOverride'));

        /** @var PollableChannel $deadLetter */
        $deadLetter = $ecotone->getMessageChannel(DelayedRetryHandler::DEAD_LETTER_CHANNEL);
        /** @var PollableChannel $globalDefault */
        $globalDefault = $ecotone->getMessageChannel('globalDefaultErrorChannel');

        $this->assertNotNull($deadLetter->receive(), '#[DelayedRetry] must route the failure to its own dead letter channel');
        $this->assertNull($globalDefault->receive(), 'Global default error channel must not receive the failure when handler declares #[DelayedRetry]');
    }

    public function test_async_handler_with_error_channel_directly_on_method_throws_descriptive_error(): void
    {
        $service = new class () {
            #[Asynchronous('asyncMisplacedErrorChannel')]
            #[\Ecotone\Api\Attribute\ErrorChannel('someErrorChannel')]
            #[CommandHandler('misplaced.errorchannel', 'misplacedErrorChannelHandler')]
            public function handle(string $payload): void
            {
            }
        };

        $this->expectException(\Ecotone\Messaging\Config\ConfigurationException::class);
        $this->expectExceptionMessage('#[ErrorChannel]');
        $this->expectExceptionMessage('asynchronousExecution');

        EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel('asyncMisplacedErrorChannel', delayable: false),
                    SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel', delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_async_handler_with_delayed_retry_directly_on_method_throws_descriptive_error(): void
    {
        $service = new class () {
            #[Asynchronous('asyncMisplacedDelayedRetry')]
            #[\Ecotone\Api\Attribute\DelayedRetry(initialDelayInMilliseconds: 1, maxRetries: 2)]
            #[CommandHandler('misplaced.delayedretry', 'misplacedDelayedRetryHandler')]
            public function handle(string $payload): void
            {
            }
        };

        $this->expectException(\Ecotone\Messaging\Config\ConfigurationException::class);
        $this->expectExceptionMessage('#[DelayedRetry]');
        $this->expectExceptionMessage('asynchronousExecution');

        EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel('asyncMisplacedDelayedRetry', delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_delayed_retry_on_inbound_channel_adapter_throws_descriptive_error(): void
    {
        $service = new class () {
            #[\Ecotone\Api\Attribute\DelayedRetry(initialDelayInMilliseconds: 1, maxRetries: 2)]
            #[\Ecotone\Api\Attribute\Scheduled('inboundDelayedRetryChannel', 'inboundDelayedRetry')]
            #[\Ecotone\Api\Attribute\Poller(executionTimeLimitInMilliseconds: 1, handledMessageLimit: 1)]
            public function emit(): string
            {
                return 'payload';
            }
        };

        $this->expectException(\Ecotone\Messaging\Config\ConfigurationException::class);
        $this->expectExceptionMessage('#[DelayedRetry] cannot be used on an Inbound Channel Adapter');
        $this->expectExceptionMessage('#[ErrorChannel]');
        $this->expectExceptionMessage('#[InstantRetry]');

        EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_inbound_channel_adapter_with_instant_retry_recovers_within_in_process_retries(): void
    {
        $handler = new InboundChannelAdapterWithInstantRetryAndErrorChannel();
        $handler->maxFailures = 2;

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InboundChannelAdapterWithInstantRetryAndErrorChannel::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(InboundChannelAdapterWithInstantRetryAndErrorChannel::ERROR_CHANNEL, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->run(InboundChannelAdapterWithInstantRetryAndErrorChannel::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));

        $this->assertSame(3, $handler->invocations, 'Handler must be invoked once + retried twice (retryTimes: 2) before succeeding on the third attempt');

        /** @var PollableChannel $errorChannel */
        $errorChannel = $ecotone->getMessageChannel(InboundChannelAdapterWithInstantRetryAndErrorChannel::ERROR_CHANNEL);
        $this->assertNull($errorChannel->receive(), 'Error channel must remain empty when InstantRetry recovers within retry budget');
    }

    public function test_instant_retry_on_inbound_channel_adapter_requires_enterprise_licence(): void
    {
        $this->expectException(\Ecotone\Messaging\Support\LicensingException::class);
        $this->expectExceptionMessage('Instant retry attribute is available only for Ecotone Enterprise');

        EcotoneLite::bootstrapFlowTesting(
            [InboundChannelAdapterWithInstantRetryAndErrorChannel::class],
            [new InboundChannelAdapterWithInstantRetryAndErrorChannel()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(InboundChannelAdapterWithInstantRetryAndErrorChannel::ERROR_CHANNEL, delayable: false),
                ]),
        );
    }

    public function test_inbound_channel_adapter_with_instant_retry_forwards_to_error_channel_after_retries_exhausted(): void
    {
        $handler = new InboundChannelAdapterWithInstantRetryAndErrorChannel();
        $handler->maxFailures = PHP_INT_MAX;

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InboundChannelAdapterWithInstantRetryAndErrorChannel::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withExtensionObjects([
                    \Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel(InboundChannelAdapterWithInstantRetryAndErrorChannel::ERROR_CHANNEL, delayable: false),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->run(InboundChannelAdapterWithInstantRetryAndErrorChannel::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(
            handledMessageLimit: 1,
            stopOnError: false,
        ));

        $this->assertSame(3, $handler->invocations, 'Handler must be invoked once + retried twice before forwarding to Error Channel');

        /** @var PollableChannel $errorChannel */
        $errorChannel = $ecotone->getMessageChannel(InboundChannelAdapterWithInstantRetryAndErrorChannel::ERROR_CHANNEL);
        $this->assertNotNull($errorChannel->receive(), 'Failed message must land in the configured Error Channel after InstantRetry retries are exhausted');
    }

    public function test_an_error_channel_without_a_handler_names_the_failed_message_it_could_not_dispatch(): void
    {
        $handler = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $order): void
            {
                throw new RuntimeException('order could not be placed');
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withDefaultErrorChannel('unhandledOrderFailures')
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('orders', finalFailureStrategy: FinalFailureStrategy::STOP),
                    SimpleMessageChannelBuilder::createDirectMessageChannel('unhandledOrderFailures'),
                ]),
        );
        $messageId = Uuid::v7()->toRfc4122();
        $ecotone->sendCommandWithRouting('order.place', 'coffee', metadata: [MessageHeaders::MESSAGE_ID => $messageId]);

        $this->expectException(MessageDispatchingException::class);
        $this->expectExceptionMessageMatches('/^There is no message handler registered for dispatching Message .*' . $messageId . '/');

        $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class FailingScheduledExample
{
    public const ENDPOINT_ID = 'failing.scheduler';
    public const REQUEST_CHANNEL = 'failing.scheduler.input';

    #[\Ecotone\Api\Attribute\Scheduled(self::REQUEST_CHANNEL, self::ENDPOINT_ID)]
    #[\Ecotone\Api\Attribute\Poller(executionTimeLimitInMilliseconds: 1, handledMessageLimit: 1)]
    public function poll(): string
    {
        return 'payload';
    }

    #[\Ecotone\Api\Attribute\InternalHandler(self::REQUEST_CHANNEL)]
    public function handle(string $payload): void
    {
        throw new InvalidArgumentException('boom');
    }
}

/**
 * licence Enterprise
 *
 * Two async command handlers share the same async transport channel,
 * but each declares its own #[ErrorChannel] via #[Asynchronous] asynchronousExecution.
 *
 * @internal
 */
final class AsyncFailingHandler
{
    public const SHARED_ASYNC_CHANNEL = 'sharedAsync';
    public const ROUTING_KEY_A = 'async.handler.a';
    public const ROUTING_KEY_B = 'async.handler.b';
    public const ERROR_CHANNEL_A = 'errorChannelA';
    public const ERROR_CHANNEL_B = 'errorChannelB';

    #[Asynchronous(self::SHARED_ASYNC_CHANNEL, asynchronousExecution: [new \Ecotone\Api\Attribute\ErrorChannel(self::ERROR_CHANNEL_A)])]
    #[CommandHandler(self::ROUTING_KEY_A, 'asyncHandlerA')]
    public function handleA(string $payload): void
    {
        throw new RuntimeException('handler-a-failure');
    }

    #[Asynchronous(self::SHARED_ASYNC_CHANNEL, asynchronousExecution: [new \Ecotone\Api\Attribute\ErrorChannel(self::ERROR_CHANNEL_B)])]
    #[CommandHandler(self::ROUTING_KEY_B, 'asyncHandlerB')]
    public function handleB(string $payload): void
    {
        throw new RuntimeException('handler-b-failure');
    }
}

/**
 * licence Enterprise
 *
 * @internal
 */
final class DelayedRetryHandler
{
    public const ASYNC_CHANNEL = 'delayedRetryAsync';
    public const ROUTING_KEY_RECOVERS = 'retry.recovers';
    public const ROUTING_KEY_DEAD_LETTER = 'retry.deadletter';
    public const ROUTING_KEY_OVERRIDE = 'retry.override';
    public const DEAD_LETTER_CHANNEL = 'retryDeadLetterChannel';

    public int $attemptsRecovers = 0;
    public int $attemptsDeadLetter = 0;
    public int $attemptsOverride = 0;
    public bool $finallyHandled = false;

    #[Asynchronous(self::ASYNC_CHANNEL, asynchronousExecution: [
        new \Ecotone\Api\Attribute\DelayedRetry(initialDelayInMilliseconds: 1, multiplier: 1, maxRetries: 3),
    ])]
    #[CommandHandler(self::ROUTING_KEY_RECOVERS, 'retryRecovers')]
    public function recovers(string $payload): void
    {
        $this->attemptsRecovers++;
        if ($this->attemptsRecovers < 2) {
            throw new RuntimeException('transient');
        }
        $this->finallyHandled = true;
    }

    #[Asynchronous(self::ASYNC_CHANNEL, asynchronousExecution: [
        new \Ecotone\Api\Attribute\DelayedRetry(
            initialDelayInMilliseconds: 1,
            multiplier: 1,
            maxRetries: 2,
            deadLetterChannel: self::DEAD_LETTER_CHANNEL,
        ),
    ])]
    #[CommandHandler(self::ROUTING_KEY_DEAD_LETTER, 'retryDeadLetter')]
    public function alwaysFails(string $payload): void
    {
        $this->attemptsDeadLetter++;
        throw new RuntimeException('permanent');
    }

    #[Asynchronous(self::ASYNC_CHANNEL, asynchronousExecution: [
        new \Ecotone\Api\Attribute\DelayedRetry(
            initialDelayInMilliseconds: 1,
            multiplier: 1,
            maxRetries: 1,
            deadLetterChannel: self::DEAD_LETTER_CHANNEL,
        ),
    ])]
    #[CommandHandler(self::ROUTING_KEY_OVERRIDE, 'retryOverride')]
    public function alwaysFailsOverridingDefault(string $payload): void
    {
        $this->attemptsOverride++;
        throw new RuntimeException('permanent');
    }

    #[\Ecotone\Api\Attribute\QueryHandler('retryHandler.attemptsRecovers')]
    public function getAttemptsRecovers(): int
    {
        return $this->attemptsRecovers;
    }

    #[\Ecotone\Api\Attribute\QueryHandler('retryHandler.attemptsDeadLetter')]
    public function getAttemptsDeadLetter(): int
    {
        return $this->attemptsDeadLetter;
    }

    #[\Ecotone\Api\Attribute\QueryHandler('retryHandler.attemptsOverride')]
    public function getAttemptsOverride(): int
    {
        return $this->attemptsOverride;
    }

    #[\Ecotone\Api\Attribute\QueryHandler('retryHandler.finallyHandled')]
    public function isFinallyHandled(): bool
    {
        return $this->finallyHandled;
    }
}

/**
 * licence Enterprise
 *
 * #[InstantRetry] retries the handler in-process before forwarding to #[ErrorChannel].
 *
 * @internal
 */
final class InboundChannelAdapterWithInstantRetryAndErrorChannel
{
    public const ENDPOINT_ID = 'inboundInstantRetry';
    public const REQUEST_CHANNEL = 'inboundInstantRetryChannel';
    public const ERROR_CHANNEL = 'inboundInstantRetryErrorChannel';

    public int $invocations = 0;
    public int $maxFailures = 0;
    public bool $hasEmitted = false;

    #[\Ecotone\Api\Attribute\InstantRetry(retryTimes: 2)]
    #[\Ecotone\Api\Attribute\ErrorChannel(self::ERROR_CHANNEL)]
    #[\Ecotone\Api\Attribute\Scheduled(self::REQUEST_CHANNEL, self::ENDPOINT_ID)]
    #[\Ecotone\Api\Attribute\Poller(executionTimeLimitInMilliseconds: 1, handledMessageLimit: 1)]
    public function emit(): ?string
    {
        if ($this->hasEmitted) {
            return null;
        }
        $this->hasEmitted = true;

        return 'payload';
    }

    #[\Ecotone\Api\Attribute\InternalHandler(self::REQUEST_CHANNEL)]
    public function handle(string $payload): void
    {
        $this->invocations++;
        if ($this->invocations <= $this->maxFailures) {
            throw new RuntimeException('simulated');
        }
    }
}
