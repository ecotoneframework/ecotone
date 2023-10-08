<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService;

/**
 * @covers \Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor
 * @internal
 */
final class HeaderPropagationTest extends TestCase
{
    public function test_will_provide_propagate_correlation_id_header()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
        );

        $messageId = Uuid::uuid4()->toString();
        $correlationId = Uuid::uuid4()->toString();

        $headers = $ecotoneTestSupport
            ->sendCommandWithRoutingKey(
                'placeOrder',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                    MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId,
                ]
            )
            ->getRecordedEventHeaders()[0];

        $this->assertNotSame($messageId, $headers->getMessageId());
        $this->assertSame($correlationId, $headers->getCorrelationId());
    }

    public function test_will_provide_message_parent_id_if_message_id_have_changed()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
        );

        $messageId = Uuid::uuid4()->toString();
        $headers = $ecotoneTestSupport
            ->sendCommandWithRoutingKey(
                'placeOrder',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                ]
            )
            ->getRecordedEventHeaders()[0];

        $this->assertNotSame($messageId, $headers->getMessageId());
        $this->assertSame($messageId, $headers->getParentId());
    }

    public function test_will_not_provide_message_parent_id_if_message_id_is_same()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
        );

        $messageId = Uuid::uuid4()->toString();
        $headers = $ecotoneTestSupport
            ->sendCommandWithRoutingKey(
                'placeOrderAndPropagateMetadata',
                metadata: [
                    MessageHeaders::MESSAGE_ID => $messageId,
                ]
            )
            ->getRecordedEventHeaders()[0];

        $this->assertSame($messageId, $headers->getMessageId());
        $this->assertNull($headers->getParentId());
    }
}
