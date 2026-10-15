<?php

declare(strict_types=1);

namespace Modelling\Unit;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\AsynchronousPaymentHandler;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command\TakePayment;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\OrderProcessSaga;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\PaymentHandler;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class WorkflowTest extends TestCase
{
    public function test_workflow_with_joined_output_command_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessSaga::class, PaymentHandler::class],
            [
                PaymentHandler::class => new PaymentHandler(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $orderId = '123';
        $this->assertTrue(
            $ecotoneLite
                ->publishEvent(new OrderWasPlaced($orderId))
                ->sendQueryWithRouting('isPaymentTaken')
        );

        $this->assertEquals(
            [new TakePayment($orderId)],
            $ecotoneLite->popRecordedMessagePayloadsFrom('takePayment')
        );
    }

    public function test_workflow_with_message_filtering_on_saga(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessSaga::class, PaymentHandler::class],
            [
                PaymentHandler::class => new PaymentHandler(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $orderId = '123';
        $this->assertFalse(
            $ecotoneLite
                ->publishEvent(new OrderWasPlaced($orderId), metadata: ['shouldTakePayment' => false])
                ->sendQueryWithRouting('isPaymentTaken')
        );
    }

    public function test_workflow_with_joined_asynchronous_output_command_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessSaga::class, AsynchronousPaymentHandler::class],
            [
                AsynchronousPaymentHandler::class => new AsynchronousPaymentHandler(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
                ->withEnabledModulePackageNames([])
        );

        $orderId = '123';
        $this->assertFalse(
            $ecotoneLite
                ->publishEvent(new OrderWasPlaced($orderId))
                ->sendQueryWithRouting('isPaymentTaken')
        );

        $this->assertEquals(
            [new TakePayment($orderId)],
            $ecotoneLite->popRecordedMessagePayloadsFrom('takePayment')
        );

        $this->assertTrue(
            $ecotoneLite
                ->run('async')
                ->sendQueryWithRouting('isPaymentTaken')
        );
    }

    public function test_workflow_with_separated_output_command_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderProcessSaga::class],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $orderId = '123';

        $this->assertEquals(
            [new TakePayment($orderId)],
            $ecotoneLite
                ->publishEvent(new OrderWasPlaced($orderId))
                ->popRecordedMessagePayloadsFrom('takePayment')
        );
    }
}
