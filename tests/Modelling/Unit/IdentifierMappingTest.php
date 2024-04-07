<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderProcessWithAttributeHeadersMapping;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderProcessWithAttributePayloadMapping;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier\OrderProcess;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier\OrderProcessWithMethodBasedIdentifier;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier\OrderStarted;
use Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier\OrderStartedAsynchronous;

/**
 * @internal
 */
final class IdentifierMappingTest extends TestCase
{
    /**
     * @dataProvider sagasTypes
     */
    public function test_mapping_using_target_identifier_for_events(string $sagaClass): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$sagaClass],
        );

        $this->assertEquals(
            '123',
            $ecotoneLite
                ->publishEvent(new OrderStarted('123'))
                ->getSaga($sagaClass, '123')
                ->getOrderId()
        );
    }

    /**
     * @dataProvider sagasTypes
     */
    public function test_mapping_using_target_identifier_for_events_when_endpoint_is_asynchronous(string $sagaClass): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$sagaClass],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $this->assertEquals(
            '123',
            $ecotoneLite
                ->publishEvent(new OrderStartedAsynchronous('123'))
                ->run('async')
                ->getSaga($sagaClass, '123')
                ->getOrderId()
        );
    }

    public function test_mapping_using_attribute_mapper_from_payload(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessWithAttributePayloadMapping::class],
        );

        $this->assertEquals(
            'new',
            $ecotoneLite
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStarted(
                    '123',
                    'new'
                ))
                ->getSaga(OrderProcessWithAttributePayloadMapping::class, '123')
                ->getStatus()
        );
    }

    public function test_mapping_with_redirect_to_action_method(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessWithAttributePayloadMapping::class],
        );

        $this->assertEquals(
            'closed',
            $ecotoneLite
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStarted(
                    '123',
                    'new'
                ))
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStarted(
                    '123',
                    'closed'
                ))
                ->getSaga(OrderProcessWithAttributePayloadMapping::class, '123')
                ->getStatus()
        );
    }

    public function test_mapping_using_attribute_mapper_from_payload_when_asynchronous(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessWithAttributePayloadMapping::class],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $this->assertEquals(
            'new',
            $ecotoneLite
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStartedAsynchronous(
                    '123',
                    'new'
                ))
                ->run('async')
                ->getSaga(OrderProcessWithAttributePayloadMapping::class, '123')
                ->getStatus()
        );
    }

    public function test_mapping_with_redirect_to_action_method_when_asynchronous(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessWithAttributePayloadMapping::class],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $this->assertEquals(
            'closed',
            $ecotoneLite
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStartedAsynchronous(
                    '123',
                    'new'
                ))
                ->run('async')
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStartedAsynchronous(
                    '123',
                    'closed'
                ))
                ->run('async')
                ->getSaga(OrderProcessWithAttributePayloadMapping::class, '123')
                ->getStatus()
        );
    }

    public function test_mapping_using_attribute_mapper_from_header(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessWithAttributeHeadersMapping::class],
        );

        $this->assertEquals(
            'ongoing',
            $ecotoneLite
                ->sendCommandWithRoutingKey('startOrder', '123')
                ->publishEvent(new \Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping\OrderStarted(
                    '',
                    'ongoing'
                ), metadata: [
                    'orderId' => '123',
                ])
                ->getSaga(OrderProcessWithAttributeHeadersMapping::class, '123')
                ->getStatus()
        );
    }

    public static function sagasTypes(): iterable
    {
        yield 'Property based identifier' => [OrderProcess::class];
        yield 'Method based identifier' => [OrderProcessWithMethodBasedIdentifier::class];
    }
}
