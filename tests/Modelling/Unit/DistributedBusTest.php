<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\MessageHandling\Distribution\DistributionEntrypoint;
use Ecotone\Modelling\MessageHandling\Distribution\RoutingKeyIsNotDistributed;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\DistributedCommandHandler\ShoppingCenter;
use Test\Ecotone\Modelling\Fixture\DistributedEventHandler\ShoppingRecord;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class DistributedBusTest extends TestCase
{
    public function test_distribute_command(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [ShoppingCenter::class],
            [
                new ShoppingCenter(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $distributionEntrypoint = $ecotoneTestSupport->getGateway(DistributionEntrypoint::class);

        $this->assertEquals(0, $ecotoneTestSupport->sendQueryWithRouting(ShoppingCenter::COUNT_BOUGHT_GOODS, ));

        $distributionEntrypoint->distributeMessage('milk', [DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => ShoppingCenter::SHOPPING_BUY, DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'command'], MediaType::TEXT_PLAIN);
        $this->assertEquals(1, $ecotoneTestSupport->sendQueryWithRouting(ShoppingCenter::COUNT_BOUGHT_GOODS, ));
    }

    public function test_distribute_event(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [ShoppingRecord::class],
            [
                new ShoppingRecord(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $distributionEntrypoint = $ecotoneTestSupport->getGateway(DistributionEntrypoint::class);

        $this->assertEquals(0, $ecotoneTestSupport->sendQueryWithRouting(ShoppingRecord::COUNT_BOUGHT_GOODS, ));

        $distributionEntrypoint->distributeMessage('milk', [DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => ShoppingRecord::ORDER_WAS_MADE, DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'event'], MediaType::TEXT_PLAIN);
        $this->assertEquals(1, $ecotoneTestSupport->sendQueryWithRouting(ShoppingRecord::COUNT_BOUGHT_GOODS, ));
    }

    public function test_not_calling_when_event_handler_not_distributed(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [ShoppingRecord::class],
            [
                new ShoppingRecord(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $distributionEntrypoint = $ecotoneTestSupport->getGateway(DistributionEntrypoint::class);

        $this->assertEquals(0, $ecotoneTestSupport->sendQueryWithRouting(ShoppingRecord::COUNT_BOUGHT_GOODS));

        $distributionEntrypoint->distributeMessage('milk', [DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => ShoppingRecord::ORDER_WAS_MADE_NON_DISTRIBUTED, DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'event'], MediaType::TEXT_PLAIN);
        $this->assertEquals(0, $ecotoneTestSupport->sendQueryWithRouting(ShoppingRecord::COUNT_BOUGHT_GOODS));
    }

    public function test_throwing_exception_if_trying_to_handle_not_distributed_routing_key(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [ShoppingCenter::class],
            [
                new ShoppingCenter(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $distributionEntrypoint = $ecotoneTestSupport->getGateway(DistributionEntrypoint::class);

        $this->expectException(RoutingKeyIsNotDistributed::class);

        $distributionEntrypoint->distributeMessage('milk', [DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => ShoppingCenter::SHOPPING_BUY_NOT_DISTRIBUTED, DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'command'], MediaType::TEXT_PLAIN);
    }
}
