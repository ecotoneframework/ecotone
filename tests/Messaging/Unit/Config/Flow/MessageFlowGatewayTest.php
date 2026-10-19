<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowGatewayTest extends TestCase
{
    public function test_gateway_multiple_methods_preserves_the_call_and_request_channel(): void
    {
        $service = new class () {
            public BusinessGatewayForFlow $gateway;

            #[CommandHandler('gateway.trigger')]
            public function handle(): void
            {
                $this->gateway->ship('order', 'ship');
                $this->gateway->cancel('order');
            }

            #[InternalHandler('gateway.ship')]
            public function ship(string $payload): void
            {
            }

            #[InternalHandler('gateway.cancel')]
            public function cancel(string $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, BusinessGatewayForFlow::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $gateway = 'gateway:' . BusinessGatewayForFlow::class . '::ship';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::CALLS, 'handler:' . $service::class . '::handle', $gateway));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $gateway, 'channel:gateway.ship'));
        self::assertCount(2, $graph->nodesOfKind(FlowNodeKind::GATEWAY));
        self::assertCount(2, $graph->edgesOfKind(FlowEdgeKind::CALLS, 'handler:' . $service::class . '::handle'));
    }

    public function test_gateway_parent_property_preserves_the_call_and_request_channel(): void
    {
        $service = new class () extends GatewayOwnerForFlow {
            #[CommandHandler('gateway.trigger')]
            public function handle(): void
            {
                $this->gateway->ship('order', 'ship');
            }

            #[InternalHandler('gateway.ship')]
            public function ship(string $payload): void
            {
            }

            #[InternalHandler('gateway.cancel')]
            public function cancel(string $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, BusinessGatewayForFlow::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $gateway = 'gateway:' . BusinessGatewayForFlow::class . '::ship';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::CALLS, 'handler:' . $service::class . '::handle', $gateway));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $gateway, 'channel:gateway.ship'));
        self::assertCount(2, $graph->nodesOfKind(FlowNodeKind::GATEWAY));
    }

    public function test_gateway_extended_interface_preserves_the_call_and_request_channel(): void
    {
        $service = new class () {
            public ExtendedBusinessGatewayForFlow $gateway;

            #[CommandHandler('gateway.trigger')]
            public function handle(): void
            {
                $this->gateway->cancel('order');
            }

            #[InternalHandler('gateway.ship')]
            public function ship(string $payload): void
            {
            }

            #[InternalHandler('gateway.cancel')]
            public function cancel(string $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, ExtendedBusinessGatewayForFlow::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $gateway = 'gateway:' . ExtendedBusinessGatewayForFlow::class . '::cancel';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::CALLS, 'handler:' . $service::class . '::handle', $gateway));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $gateway, 'channel:gateway.cancel'));
        self::assertCount(2, $graph->nodesOfKind(FlowNodeKind::GATEWAY));
    }

    public function test_gateway_routing_header_preserves_the_call_and_request_channel(): void
    {
        $service = new class () {
            public BusinessGatewayForFlow $gateway;

            #[CommandHandler('gateway.trigger')]
            public function handle(): void
            {
                $this->gateway->ship('order', 'shipping.express');
            }

            #[InternalHandler('gateway.ship')]
            public function ship(string $payload): void
            {
            }

            #[InternalHandler('gateway.cancel')]
            public function cancel(string $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, BusinessGatewayForFlow::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $gateway = 'gateway:' . BusinessGatewayForFlow::class . '::ship';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::CALLS, 'handler:' . $service::class . '::handle', $gateway));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $gateway, 'channel:gateway.ship'));
        self::assertCount(2, $graph->nodesOfKind(FlowNodeKind::GATEWAY));
    }

}

interface BusinessGatewayForFlow
{
    #[MessageGateway('gateway.ship')]
    public function ship(string $payload, #[Header('routingKey')] string $routingKey): void;

    #[MessageGateway('gateway.cancel')]
    public function cancel(string $payload): void;
}

interface ExtendedBusinessGatewayForFlow extends BusinessGatewayForFlow
{
}

abstract class GatewayOwnerForFlow
{
    protected BusinessGatewayForFlow $gateway;
}
