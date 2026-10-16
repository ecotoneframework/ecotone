<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ErrorContext;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Test\ChannelTestSupport;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\AsyncOrderForwarder;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\AsyncOrderSubscriber;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\FakeTransactionModule;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublishingChannelBuilder;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OperationsLog;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderRequestReceived;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderService;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderWasPlaced;

/**
 * licence Apache-2.0
 * @internal
 */
final class HighThroughputPublishingScenariosTest extends TestCase
{
    public function test_nested_command_bus_awaits_deliveries_once_at_outermost_boundary(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog);

        $ecotoneLite->sendCommandWithRouting('order.forward', 'espresso');

        $this->assertSame(
            [
                'transaction started',
                'forwarding command handler executed',
                'command handler executed',
                'published batch of 2 messages to broker',
                'delivery confirmations awaited',
                'transaction committed',
            ],
            $operationsLog->getOperations(),
        );
    }

    public function test_polling_consumer_awaits_deliveries_before_acknowledging_inbound_message(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog);

        $ecotoneLite->publishEvent(new OrderRequestReceived('espresso'));
        $this->assertSame([], $operationsLog->getOperations());

        $ecotoneLite->run('incoming_orders', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(
            [
                'transaction started',
                'consumer handler executed',
                'published batch of 1 messages to broker',
                'delivery confirmations awaited',
                'transaction committed',
            ],
            $operationsLog->getOperations(),
        );
        $this->assertEquals(
            new OrderWasPlaced('espresso-forwarded'),
            ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('async_orders')->getPayload(),
        );
    }

    public function test_bus_driven_sends_outside_any_scope_fall_back_to_synchronous_awaiting(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog);

        $ecotoneLite->publishEvent(new OrderWasPlaced('espresso-1'));

        $this->assertSame(
            [
                'published message to broker',
                'delivery confirmations awaited',
            ],
            $operationsLog->getOperations(),
        );
        $this->assertEquals(
            new OrderWasPlaced('espresso-1'),
            ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('async_orders')->getPayload(),
        );
    }

    public function test_failed_deliveries_are_routed_to_error_channel_and_transaction_commits(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, FakeTransactionModule::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            extensionObjects: [
                GlobalPollableChannelConfiguration::createWithDefaults()->withErrorChannel('failure_channel'),
                PollableChannelConfiguration::neverRetry('async_orders')->withCollector(false)->withErrorChannel('failure_channel'),
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
                SimpleMessageChannelBuilder::createQueueChannel('failure_channel'),
            ],
            enabledModules: [],
        );
        $channel = ChannelTestSupport::of($ecotoneLite)->getMessageChannel('async_orders');
        assert($channel instanceof MessageChannelInterceptorAdapter);
        $channel->getInternalMessageChannel()->failDeliveriesWith('broker not available');

        $ecotoneLite->sendCommandWithRouting('order.place', 'espresso');

        $this->assertSame('transaction committed', $operationsLog->getOperations()[count($operationsLog->getOperations()) - 1]);

        $firstFailedMessage = ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('failure_channel');
        $secondFailedMessage = ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('failure_channel');
        $this->assertStringContainsString('espresso-1', $firstFailedMessage->getPayload());
        $this->assertStringContainsString('espresso-2', $secondFailedMessage->getPayload());
        $this->assertStringContainsString(OrderWasPlaced::class, $firstFailedMessage->getHeaders()->get(MessageHeaders::TYPE_ID));
        $this->assertStringContainsString('broker not available', $firstFailedMessage->getHeaders()->get(ErrorContext::EXCEPTION_MESSAGE));
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('failure_channel'));
    }

    public function test_only_failed_message_from_batch_is_routed_to_error_channel(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, FakeTransactionModule::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            extensionObjects: [
                GlobalPollableChannelConfiguration::createWithDefaults()->withErrorChannel('failure_channel'),
                PollableChannelConfiguration::neverRetry('async_orders')->withCollector(false)->withErrorChannel('failure_channel'),
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
                SimpleMessageChannelBuilder::createQueueChannel('failure_channel'),
            ],
            enabledModules: [],
        );
        $channel = ChannelTestSupport::of($ecotoneLite)->getMessageChannel('async_orders');
        assert($channel instanceof MessageChannelInterceptorAdapter);
        $channel->getInternalMessageChannel()->failDeliveriesContaining('espresso-2', 'broker rejected message');

        $ecotoneLite->sendCommandWithRouting('order.place', 'espresso');

        $this->assertSame('transaction committed', $operationsLog->getOperations()[count($operationsLog->getOperations()) - 1]);

        $failedMessage = ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('failure_channel');
        $this->assertStringContainsString('espresso-2', $failedMessage->getPayload());
        $this->assertStringContainsString('broker rejected message', $failedMessage->getHeaders()->get(ErrorContext::EXCEPTION_MESSAGE));
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('failure_channel'));
    }

    private function bootstrapEcotone(OperationsLog $operationsLog): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, AsyncOrderForwarder::class, FakeTransactionModule::class],
            [
                new OrderService($operationsLog),
                new AsyncOrderSubscriber(),
                new AsyncOrderForwarder($operationsLog),
                OperationsLog::class => $operationsLog,
            ],
            extensionObjects: [
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
                SimpleMessageChannelBuilder::createQueueChannel('incoming_orders'),
            ],
            enabledModules: [],
        );
    }
}
