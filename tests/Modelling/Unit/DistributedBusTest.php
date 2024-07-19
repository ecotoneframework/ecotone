<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\DistributionEntrypoint;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\DistributedCommandHandler\ShoppingCenter;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class DistributedBusTest extends TestCase
{
    public function test_trying_distributed_bus_as_message()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapForTesting(
            [ShoppingCenter::class],
            [
                new ShoppingCenter(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $distributedBus = $ecotoneTestSupport->getGatewayByName(DistributionEntrypoint::class);

        $this->assertEquals(0, $ecotoneTestSupport->getQueryBus()->sendWithRouting(ShoppingCenter::COUNT_BOUGHT_GOODS, ));

        $distributedBus->distributeMessage('milk', [DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY => ShoppingCenter::SHOPPING_BUY, DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE => 'command'], MediaType::TEXT_PLAIN);
        $this->assertEquals(1, $ecotoneTestSupport->getQueryBus()->sendWithRouting(ShoppingCenter::COUNT_BOUGHT_GOODS, ));
    }
}
