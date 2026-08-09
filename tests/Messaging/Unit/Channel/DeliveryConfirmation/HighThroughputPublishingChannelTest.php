<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PublishingFailedException;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\AsyncOrderSubscriber;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\FakeTransactionModule;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublishingChannelBuilder;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OperationsLog;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderService;

/**
 * licence Apache-2.0
 * @internal
 */
final class HighThroughputPublishingChannelTest extends TestCase
{
    public function test_messages_published_from_command_handler_are_awaited_before_transaction_commits(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog);

        $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');

        $this->assertSame(
            [
                'transaction started',
                'command handler executed',
                'published batch of 2 messages to broker',
                'delivery confirmations awaited',
                'transaction committed',
            ],
            $operationsLog->getOperations(),
        );
    }

    public function test_failed_delivery_confirmation_fails_command_execution_and_rolls_back_transaction(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog);
        $channel = $ecotoneLite->getMessageChannel('async_orders');
        assert($channel instanceof MessageChannelInterceptorAdapter);
        $channel->getInternalMessageChannel()->failDeliveriesWith('broker not available');

        $commandException = null;
        try {
            $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');
        } catch (PublishingFailedException $exception) {
            $commandException = $exception;
        }

        $this->assertInstanceOf(PublishingFailedException::class, $commandException);
        $this->assertStringContainsString('broker not available', $commandException->getMessage());
        $this->assertSame(
            [
                'transaction started',
                'command handler executed',
                'published batch of 2 messages to broker',
                'delivery confirmations awaited',
                'transaction rolled back',
            ],
            $operationsLog->getOperations(),
        );
    }

    private function bootstrapEcotone(OperationsLog $operationsLog): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, FakeTransactionModule::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            enableAsynchronousProcessing: [
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
            ],
        );
    }
}
