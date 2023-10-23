<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\MetadataPropagatingWithDoubleEventHandlers\OrderService;

/**
 * @internal
 */
final class MetadataPropagatingTest extends TestCase
{
    public function test_propagating_headers_to_all_published_synchronous_event_handlers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()]
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey(
            'placeOrder',
            metadata: [
                'userId' => '123',
            ]
        );

        $notifications = $ecotoneTestSupport->sendQueryWithRouting('getAllNotificationHeaders');
        $this->assertCount(2, $notifications);
        $this->assertEquals('123', $notifications[0]['userId']);
        $this->assertEquals('123', $notifications[1]['userId']);
    }

    public function test_propagating_headers_to_all_published_asynchronous_event_handlers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            configuration: ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('orders'),
                ])
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey(
            'placeOrder',
            metadata: [
                'userId' => '123',
            ]
        );

        $ecotoneTestSupport->run('orders', ExecutionPollingMetadata::createWithTestingSetup(2));
        $notifications = $ecotoneTestSupport->sendQueryWithRouting('getAllNotificationHeaders');

        $this->assertCount(2, $notifications);
        $this->assertEquals('123', $notifications[0]['userId']);
        $this->assertEquals('123', $notifications[1]['userId']);
    }

    public function test_not_propagating_polling_metadata(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            configuration: ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('orders'),
                ])
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey('sendNotificationViaCommandBus');

        $ecotoneTestSupport->run('orders', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertArrayNotHasKey(
            MessageHeaders::CONSUMER_POLLING_METADATA,
            $ecotoneTestSupport->getMessageChannel('orders')->receive()->getHeaders()->headers()
        );
    }
}
