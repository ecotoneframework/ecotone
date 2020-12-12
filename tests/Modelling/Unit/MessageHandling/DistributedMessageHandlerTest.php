<?php

namespace Test\Ecotone\Modelling\Unit\MessageHandling;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedMessageHandler;
use Ecotone\Modelling\MessageHandling\Distribution\RoutingKeyIsNotDistributed;
use Ecotone\Modelling\StorageCommandBus;
use Ecotone\Modelling\StorageEventBus;
use PHPUnit\Framework\TestCase;

class DistributedMessageHandlerTest extends TestCase
{
    public function test_distribute_event()
    {
        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler(["order.was_placed"], []);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "event",
            "order.was_placed",
            MediaType::TEXT_PLAIN,
            $commandBus,
            $eventBus
        );

        $this->assertEquals(
            [[
                "order.was_placed", "some", MediaType::TEXT_PLAIN, ["token" => 1]
            ]],
            $eventBus->getCalls()
        );
    }

    public function test_not_calling_when_event_handler_not_distributed()
    {
        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler([], []);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "event",
            "order.was_placed",
            MediaType::TEXT_PLAIN,
            $commandBus,
            $eventBus
        );

        $this->assertEquals(
            [],
            $eventBus->getCalls()
        );
    }

    public function test_distribute_command()
    {
        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler([], ["order.place_order"]);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "command",
            "order.place_order",
            MediaType::TEXT_PLAIN,
            $commandBus,
            $eventBus
        );

        $this->assertEquals(
            [[
                "order.place_order", "some", MediaType::TEXT_PLAIN, ["token" => 1]
            ]],
            $commandBus->getCalls()
        );
    }

    public function test_throwing_exception_if_trying_to_handle_not_distributed_routing_key()
    {
        $this->expectException(RoutingKeyIsNotDistributed::class);

        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler([], []);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "command",
            "order.place_order",
            MediaType::TEXT_PLAIN,
            $commandBus,
            $eventBus
        );
    }
}