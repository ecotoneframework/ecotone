<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\ChannelTestSupport;
use Ecotone\Test\LicenceTesting;
use Ecotone\Test\StaticMessageBuilder;
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $batch = BatchMessage::constructEmpty()
            ->append('first order')
            ->append('second order', ['priority' => 5]);

        ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->send(
            StaticMessageBuilder::withPayload($batch)->build()
        );

        $firstMessage = ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders');
        $secondMessage = ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders');

        $this->assertSame('first order', $firstMessage->getPayload());
        $this->assertSame('second order', $secondMessage->getPayload());
        $this->assertSame(5, $secondMessage->getHeaders()->get('priority'));
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders'));
    }

    public function test_batch_message_sent_to_handler_output_channel_is_split_into_individual_messages(): void
    {
        $orderProcessor = $this->createOrderProcessor();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$orderProcessor::class],
            [$orderProcessor],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRouting('order.placeAll', ['espresso', 'latte']);

        $this->assertSame('espresso', ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders')->getPayload());
        $this->assertSame('latte', ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders')->getPayload());
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders'));
    }

    public function test_batch_message_sent_to_handler_output_channel_requires_enterprise_licence(): void
    {
        $orderProcessor = $this->createOrderProcessor();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$orderProcessor::class],
            [$orderProcessor],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $this->expectException(LicensingException::class);

        $ecotoneLite->sendCommandWithRouting('order.placeAll', ['espresso', 'latte']);
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->send(
            StaticMessageBuilder::withPayload(BatchMessage::constructEmpty())->build()
        );

        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->receiveMessageFrom('orders'));
    }
}
