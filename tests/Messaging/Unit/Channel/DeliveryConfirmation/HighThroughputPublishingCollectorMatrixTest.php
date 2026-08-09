<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\AsyncOrderSubscriber;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\FakeTransactionModule;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublishingChannelBuilder;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OperationsLog;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderService;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderWasPlaced;

/**
 * licence Apache-2.0
 * @internal
 */
final class HighThroughputPublishingCollectorMatrixTest extends TestCase
{
    public function test_with_collector_disabled_messages_fire_directly_during_handler_and_await_before_commit(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = $this->bootstrapEcotone($operationsLog, collectorEnabled: false);

        $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');

        $this->assertSame(
            [
                'transaction started',
                'command handler executed',
                'published message to broker',
                'published message to broker',
                'delivery confirmations awaited',
                'delivery confirmations awaited',
                'transaction committed',
            ],
            $operationsLog->getOperations(),
        );
    }

    public function test_messages_are_consumable_from_channel_with_and_without_collector(): void
    {
        foreach ([true, false] as $collectorEnabled) {
            $ecotoneLite = $this->bootstrapEcotone(new OperationsLog(), $collectorEnabled);

            $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');

            $this->assertEquals(
                [new OrderWasPlaced('espresso-1'), new OrderWasPlaced('espresso-2')],
                [
                    $ecotoneLite->receiveMessageFrom('async_orders')->getPayload(),
                    $ecotoneLite->receiveMessageFrom('async_orders')->getPayload(),
                ],
            );
            $this->assertNull($ecotoneLite->receiveMessageFrom('async_orders'));
        }
    }

    private function bootstrapEcotone(OperationsLog $operationsLog, bool $collectorEnabled): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, FakeTransactionModule::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                PollableChannelConfiguration::neverRetry('async_orders')->withCollector($collectorEnabled),
            ]),
            enableAsynchronousProcessing: [
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
            ],
        );
    }
}
