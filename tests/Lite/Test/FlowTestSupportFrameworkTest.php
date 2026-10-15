<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\Order;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class FlowTestSupportFrameworkTest extends TestCase
{
    public function test_collecting_commands_routing(): void
    {
        $flowSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            configuration: self::ordersChannelConfiguration()
                ->withEnabledModulePackageNames([])
        );

        $this->assertEquals(
            [['order.register'], ['order.register', '3']],
            $flowSupport
                ->sendCommandWithRouting('order.register', new PlaceOrder('1'))
                ->sendCommandWithRouting('order.register', new PlaceOrder('3'), metadata: ['aggregate.id' => '3'])
                ->sendCommand(new PlaceOrder('2'))
                ->popRecordedCommandsWithRouting()
        );
    }

    public function test_providing_initial_state_in_form_of_state_stored_aggregate(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting([Order::class], configuration: self::ordersChannelConfiguration()->withEnabledModulePackageNames([]));

        $orderId = '1';

        $this->assertTrue(
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->sendCommandWithRouting('order.cancel', metadata: ['aggregate.id' => $orderId])
                ->run('orders', ExecutionPollingMetadata::createWithTestingSetup())
                ->getAggregate(Order::class, $orderId)
                ->isCancelled()
        );
    }

    public function test_state_stored_aggregate(): void
    {
        $flowSupport = EcotoneLite::bootstrapFlowTesting([Order::class], configuration: self::ordersChannelConfiguration()->withEnabledModulePackageNames([]));

        $this->assertEquals(
            1,
            $flowSupport
                ->sendCommandWithRouting('order.register', new PlaceOrder('1'))
                ->run('orders', ExecutionPollingMetadata::createWithTestingSetup())
                ->run('orders', ExecutionPollingMetadata::createWithTestingSetup())
                ->getAggregate(Order::class, '1')
                ->getIsNotifiedCount()
        );
    }

    private static function ordersChannelConfiguration(): ServiceConfiguration
    {
        return ServiceConfiguration::createWithDefaults()
            ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('orders'));
    }
}
