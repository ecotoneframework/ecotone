<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class BatchMessageSendingTest extends TestCase
{
    public function test_batch_message_sent_to_pollable_channel_is_delivered_as_individual_messages(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $batch = BatchMessage::constructEmpty()
            ->append('first order')
            ->append('second order', ['priority' => 5]);

        $ecotoneLite->getMessageChannel('orders')->send(
            MessageBuilder::withPayload($batch)->build()
        );

        $firstMessage = $ecotoneLite->receiveMessageFrom('orders');
        $secondMessage = $ecotoneLite->receiveMessageFrom('orders');

        $this->assertSame('first order', $firstMessage->getPayload());
        $this->assertSame('second order', $secondMessage->getPayload());
        $this->assertSame(5, $secondMessage->getHeaders()->get('priority'));
        $this->assertNull($ecotoneLite->receiveMessageFrom('orders'));
    }

    public function test_batch_message_sent_to_handler_output_channel_is_split_into_individual_messages(): void
    {
        $orderProcessor = $this->createOrderProcessor();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$orderProcessor::class],
            [$orderProcessor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('order.placeAll', ['espresso', 'latte']);

        $this->assertSame('espresso', $ecotoneLite->receiveMessageFrom('orders')->getPayload());
        $this->assertSame('latte', $ecotoneLite->receiveMessageFrom('orders')->getPayload());
        $this->assertNull($ecotoneLite->receiveMessageFrom('orders'));
    }

    public function test_batch_message_sent_to_handler_output_channel_requires_enterprise_licence(): void
    {
        $orderProcessor = $this->createOrderProcessor();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$orderProcessor::class],
            [$orderProcessor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ],
        );

        $this->expectException(LicensingException::class);

        $ecotoneLite->sendCommandWithRoutingKey('order.placeAll', ['espresso', 'latte']);
    }

    private function createOrderProcessor(): object
    {
        return new class () {
            #[CommandHandler('order.placeAll', outputChannelName: 'orders')]
            public function placeOrders(array $orders): BatchMessage
            {
                $batch = BatchMessage::constructEmpty();
                foreach ($orders as $order) {
                    $batch = $batch->append($order);
                }

                return $batch;
            }
        };
    }

    public function test_empty_batch_message_delivers_nothing(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->getMessageChannel('orders')->send(
            MessageBuilder::withPayload(BatchMessage::constructEmpty())->build()
        );

        $this->assertNull($ecotoneLite->receiveMessageFrom('orders'));
    }
}
