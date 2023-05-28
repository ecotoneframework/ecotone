<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Order\ChannelConfiguration;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;

class EcotoneLiteTest extends TestCase
{
    public function test_it_can_run_console_command(): void
    {
        $ecotone = EcotoneLite::bootstrap(
            [OrderService::class, ChannelConfiguration::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withEnvironment("test")
        );

        $ecotone->runConsoleCommand("ecotone:list", []);
        $this->expectNotToPerformAssertions();
    }
}