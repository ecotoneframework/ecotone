<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

final class FlowTestSupportFrameworkTest extends TestCase
{
    public function test_collecting_commands_routing()
    {
        $flowSupport = EcotoneLite::bootstrapForTesting(
            [OrderService::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        )->getFlowTestSupport();

        $this->assertEquals(
            [['order.register'], ['order.register', "3"]],
            $flowSupport
                ->sendCommandWithRoutingKey('order.register', new PlaceOrder("1"))
                ->sendCommandWithRoutingKey('order.register', new PlaceOrder("3"), metadata: ["aggregate.id" => "3"])
                ->sendCommand(new PlaceOrder("2"))
                ->getRecordedCommandsWithRouting()
        );
    }
}