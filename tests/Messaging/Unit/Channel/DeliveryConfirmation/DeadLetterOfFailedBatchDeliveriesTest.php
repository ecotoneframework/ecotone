<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\AsyncOrderSubscriber;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublishingChannelBuilder;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OperationsLog;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\OrderService;

/**
 * licence Apache-2.0
 * @internal
 */
final class DeadLetterOfFailedBatchDeliveriesTest extends TestCase
{
    public function test_send_retries_exhaustion_stores_each_failed_batch_delivery_as_separate_dead_letter(): void
    {
        $ecotoneLite = $this->bootstrapWithDeadLetterChannel();
        $ordersChannel = $ecotoneLite->getMessageChannel('async_orders');
        assert($ordersChannel instanceof MessageChannelInterceptorAdapter);
        $ordersChannel->getInternalMessageChannel()->failDeliveriesContaining('poison', 'nacked by broker');

        $ordersChannel->send(MessageBuilder::withPayload(
            BatchMessage::constructEmpty()
                ->append('first poison order')
                ->append('delivered order')
                ->append('second poison order')
        )->build());

        $this->assertSame(
            ['first poison order', 'second poison order'],
            $this->receiveAllPayloads($ecotoneLite->getMessageChannel('dead_letters')),
        );
    }

    public function test_failed_async_batch_deliveries_are_stored_as_separate_dead_letters(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                PollableChannelConfiguration::create('async_orders', RetryTemplateBuilder::fixedBackOff(1)->maxRetryAttempts(1)->build())
                    ->withErrorChannel('dead_letters'),
            ]),
            enableAsynchronousProcessing: [
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
                SimpleMessageChannelBuilder::createQueueChannel('dead_letters'),
            ],
        );
        $ordersChannel = $ecotoneLite->getMessageChannel('async_orders');
        assert($ordersChannel instanceof MessageChannelInterceptorAdapter);
        $ordersChannel->getInternalMessageChannel()->failDeliveriesWith('broker not available');

        $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');

        $deadLetteredPayloads = $this->receiveAllPayloads($ecotoneLite->getMessageChannel('dead_letters'));
        $this->assertCount(2, $deadLetteredPayloads);
        $this->assertStringContainsString('espresso-1', $deadLetteredPayloads[0]);
        $this->assertStringNotContainsString('espresso-2', $deadLetteredPayloads[0]);
        $this->assertStringContainsString('espresso-2', $deadLetteredPayloads[1]);
        $this->assertStringNotContainsString('espresso-1', $deadLetteredPayloads[1]);
    }

    private function bootstrapWithDeadLetterChannel(): \Ecotone\Lite\Test\FlowTestSupport
    {
        $operationsLog = new OperationsLog();

        return EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService($operationsLog), OperationsLog::class => $operationsLog],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                PollableChannelConfiguration::create('async_orders', RetryTemplateBuilder::fixedBackOff(1)->maxRetryAttempts(1)->build())
                    ->withErrorChannel('dead_letters'),
            ]),
            enableAsynchronousProcessing: [
                InMemoryHighThroughputPublishingChannelBuilder::create('async_orders'),
                SimpleMessageChannelBuilder::createQueueChannel('dead_letters'),
            ],
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function receiveAllPayloads(PollableChannel $deadLetterChannel): array
    {
        $payloads = [];
        while ($deadLetter = $deadLetterChannel->receive()) {
            $payloads[] = $this->unwrapPayload($deadLetter);
        }

        return $payloads;
    }

    private function unwrapPayload(Message $deadLetter): mixed
    {
        return $deadLetter->getPayload();
    }
}
