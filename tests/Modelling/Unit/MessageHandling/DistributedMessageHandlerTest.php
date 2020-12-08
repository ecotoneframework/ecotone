<?php

namespace Test\Ecotone\Modelling\Unit\MessageHandling;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedMessageHandler;
use Ecotone\Modelling\StorageCommandBus;
use Ecotone\Modelling\StorageEventBus;
use PHPUnit\Framework\TestCase;

class DistributedMessageHandlerTest extends TestCase
{
    public function test_distribute_event()
    {
        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler($commandBus, $eventBus);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "event",
            "order.was_placed",
            MediaType::TEXT_PLAIN
        );

        $this->assertEquals(
            [[
                "order.was_placed", "some", MediaType::TEXT_PLAIN, ["token" => 1]
            ]],
            $eventBus->getCalls()
        );
    }

    public function test_distribute_command()
    {
        $eventBus                  = StorageEventBus::create();
        $commandBus                = StorageCommandBus::create();
        $distributedMessageHandler = new DistributedMessageHandler($commandBus, $eventBus);

        $distributedMessageHandler->handle(
            "some", ["token" => 1],
            "command",
            "order.place_order",
            MediaType::TEXT_PLAIN
        );

        $this->assertEquals(
            [[
                "order.place_order", "some", MediaType::TEXT_PLAIN, ["token" => 1]
            ]],
            $commandBus->getCalls()
        );
    }
}