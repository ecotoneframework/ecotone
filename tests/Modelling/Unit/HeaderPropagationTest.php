<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\FakeLoggingGateway;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\FakeLoggingService;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\PropagatingGateway;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\PropagatingOrderService;

/**
 * @covers \Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class HeaderPropagationTest extends TestCase
{
    public function test_will_provide_propagate_correlation_id_header()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $messageId = Uuid::v7()->toRfc4122();
        $correlationId = Uuid::v7()->toRfc4122();

        $headers = $ecotoneTestSupport
            ->sendCommandWithRouting(
                'placeOrder',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                    MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId,
                ]
            )
            ->popRecordedEventHeaders()[0];

        $this->assertNotSame($messageId, $headers->getMessageId());
        $this->assertSame($correlationId, $headers->getCorrelationId());
    }

    public function test_will_provide_message_parent_id_if_message_id_have_changed()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $messageId = Uuid::v7()->toRfc4122();
        $headers = $ecotoneTestSupport
            ->sendCommandWithRouting(
                'placeOrder',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                ]
            )
            ->popRecordedEventHeaders()[0];

        $this->assertNotSame($messageId, $headers->getMessageId());
        $this->assertSame($messageId, $headers->getParentId());
    }

    public function test_will_not_provide_message_parent_id_if_message_id_is_same()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $messageId = Uuid::v7()->toRfc4122();
        $headers = $ecotoneTestSupport
            ->sendCommandWithRouting(
                'placeOrderAndPropagateMetadata',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                ]
            )
            ->popRecordedEventHeaders()[0];

        $this->assertSame($messageId, $headers->getMessageId());
        $this->assertNull($headers->getParentId());
    }

    public function test_will_propagate_custom_message_gateway()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, PropagatingGateway::class, PropagatingOrderService::class],
            [new OrderService(), new PropagatingOrderService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotoneTestSupport->getGateway(PropagatingGateway::class)->placeOrderWithPropagation([
            'token' => '123',
        ]);

        $headers = $ecotoneTestSupport->popRecordedEventHeaders()[0];

        $this->assertSame('123', $headers->get('token'));
    }

    public function test_will_not_propagate_when_propagation_of_message_gateway_is_disabled()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, PropagatingGateway::class, PropagatingOrderService::class],
            [new OrderService(), new PropagatingOrderService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotoneTestSupport->getGateway(PropagatingGateway::class)->placeOrderWithoutPropagation([
            'token' => '123',
        ]);

        $headers = $ecotoneTestSupport->popRecordedEventHeaders()[0];

        $this->assertFalse($headers->containsKey('token'));
    }

    public function test_interceptors_which_do_not_propagate_do_not_affect_event_bus_propagation()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, FakeLoggingService::class, FakeLoggingGateway::class, PropagatingGateway::class],
            [new OrderService(), new FakeLoggingService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotoneTestSupport->getGateway(PropagatingGateway::class)->placeOrderWithPropagation([
            'token' => '123',
        ]);

        $headers = $ecotoneTestSupport->popRecordedEventHeaders()[0];

        $this->assertSame('123', $headers->get('token'));
    }
}
