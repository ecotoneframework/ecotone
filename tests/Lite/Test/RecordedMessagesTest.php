<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

/**
 * licence Apache-2.0
 * @internal
 */
final class RecordedMessagesTest extends TestCase
{
    public function test_popping_recorded_events_returns_each_event_once(): void
    {
        $handler = $this->orderHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        $this->assertEquals([new OrderWasPlaced('order-1'), (object) ['notification' => 'order-1']], $ecotone->popRecordedEvents());
        $this->assertSame([], $ecotone->popRecordedEvents());

        $ecotone->sendCommandWithRouting('order.place', 'order-2');

        $this->assertEquals([new OrderWasPlaced('order-2'), (object) ['notification' => 'order-2']], $ecotone->popRecordedEvents());
    }

    public function test_popping_recorded_events_of_type_leaves_other_events_recorded(): void
    {
        $handler = $this->orderHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        $this->assertEquals([new OrderWasPlaced('order-1')], $ecotone->popRecordedEventsOfType(OrderWasPlaced::class));
        $this->assertSame([], $ecotone->popRecordedEventsOfType(OrderWasPlaced::class));
        $this->assertEquals([(object) ['notification' => 'order-1']], $ecotone->popRecordedEvents());
    }

    public function test_popping_recorded_commands_of_type_leaves_other_commands_recorded(): void
    {
        $handler = $this->orderHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        $this->assertEquals([new PlaceOrder('order-1')], $ecotone->popRecordedCommandsOfType(PlaceOrder::class));
        $this->assertSame(['order-1'], $ecotone->popRecordedCommands());
    }

    private function orderHandler(): object
    {
        return new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId, CommandBus $commandBus): void
            {
                $commandBus->send(new PlaceOrder($orderId));
            }

            #[CommandHandler]
            public function placeOrder(PlaceOrder $command, EventBus $eventBus): void
            {
                $eventBus->publish(new OrderWasPlaced($command->getOrderId()));
                $notification = new stdClass();
                $notification->notification = $command->getOrderId();
                $eventBus->publish($notification);
            }
        };
    }
}
