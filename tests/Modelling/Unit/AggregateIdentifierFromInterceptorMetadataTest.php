<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Test\Ecotone\Messaging\BaseEcotoneTestCase;
use Test\Ecotone\Modelling\Fixture\Saga\AsynchronousOrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\BeforeFinishOrder;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;
use Test\Ecotone\Modelling\Fixture\Saga\PresendFinishOrder;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateIdentifierFromInterceptorMetadataTest extends BaseEcotoneTestCase
{
    public function test_loading_aggregate_by_metadata_using_before_interceptor(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                OrderFulfilment::class,
                BeforeFinishOrder::class,
            ],
            containerOrAvailableServices: [
                new BeforeFinishOrder(),
            ],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('order.start', $orderId = 100);
        $ecotone->publishEvent(PaymentWasDoneEvent::create($orderId));

        $this->assertEquals('done', $ecotone->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $orderId]));
    }

    public function test_loading_aggregate_by_metadata_using_before_interceptor_async_scenario(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                AsynchronousOrderFulfilment::class,
                BeforeFinishOrder::class,
            ],
            containerOrAvailableServices: [
                new BeforeFinishOrder(),
            ],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('order.start', $orderId = 100);
        $ecotone->publishEvent(PaymentWasDoneEvent::create($orderId));
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertEquals('done', $ecotone->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $orderId]));
    }

    public function test_loading_aggregate_by_metadata_using_presend_interceptor(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                OrderFulfilment::class,
                PresendFinishOrder::class,
            ],
            containerOrAvailableServices: [
                new PresendFinishOrder(),
            ],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('order.start', $orderId = 100);
        $ecotone->publishEvent(PaymentWasDoneEvent::create($orderId));

        $this->assertEquals('done', $ecotone->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $orderId]));
    }

    public function test_loading_aggregate_by_metadata_using_presend_interceptor_async_scenario(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                AsynchronousOrderFulfilment::class,
                PresendFinishOrder::class,
            ],
            containerOrAvailableServices: [
                new PresendFinishOrder(),
            ],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('order.start', $orderId = 100);
        $ecotone->publishEvent(PaymentWasDoneEvent::create($orderId));
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertEquals('done', $ecotone->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $orderId]));
    }
}
