<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Messaging\Channel\DeliveryConfirmation\FailedDelivery;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PublishingFailedException;
use Ecotone\Messaging\Channel\PollableChannel\SendRetries\SendRetryChannelInterceptor;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\Scheduling\StubUTCClock;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\Test\StaticMessageBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class SendRetryOfFailedBatchDeliveriesTest extends TestCase
{
    public function test_retry_redelivers_only_failed_messages_from_batch(): void
    {
        $channel = $this->createRecordingChannel();
        $interceptor = $this->createInterceptor();

        $batchMessage = StaticMessageBuilder::withPayload(
            BatchMessage::constructEmpty()
                ->append('first delivered order')
                ->append('poison order')
                ->append('second delivered order')
        )->build();

        $interceptor->afterSendCompletion(
            $batchMessage,
            $channel,
            PublishingFailedException::withFailedDeliveries([
                new FailedDelivery(StaticMessageBuilder::withPayload('poison order')->build(), 'nacked by broker', 'orders'),
            ]),
        );

        $this->assertSame([['poison order']], $channel->sentBatchPayloads);
    }

    public function test_retry_of_single_message_failure_redelivers_original_message(): void
    {
        $channel = $this->createRecordingChannel();
        $interceptor = $this->createInterceptor();

        $singleMessage = StaticMessageBuilder::withPayload('failed order')->build();

        $interceptor->afterSendCompletion(
            $singleMessage,
            $channel,
            PublishingFailedException::withFailedDeliveries([
                new FailedDelivery($singleMessage, 'nacked by broker', 'orders'),
            ]),
        );

        $this->assertSame([['failed order']], $channel->sentBatchPayloads);
    }

    private function createInterceptor(): SendRetryChannelInterceptor
    {
        $clock = StubUTCClock::createWithCurrentTime('2025-01-01 00:00:00');

        return new SendRetryChannelInterceptor(
            'orders',
            RetryTemplateBuilder::fixedBackOff(1)->maxRetries(1)->build(),
            null,
            new ErrorChannelService(
                new LoggingService(),
                $this->createStub(OutboundMessageConverter::class),
                $this->createStub(ConversionService::class),
                new MessageHeadersPropagatorInterceptor(),
                new MessageBuilder($clock),
            ),
            $this->createStub(ConfiguredMessagingSystem::class),
            new NullLogger(),
            $clock,
            new MessageBuilder($clock),
        );
    }

    private function createRecordingChannel(): MessageChannel
    {
        return new class () implements MessageChannel {
            /** @var array<int, array<int, mixed>> */
            public array $sentBatchPayloads = [];

            public function send(Message $message): void
            {
                $payload = $message->getPayload();
                $this->sentBatchPayloads[] = $payload instanceof BatchMessage
                    ? array_map(fn (array $entry): mixed => $entry['payload'], $payload->entries)
                    : [$payload];
            }

            public function __toString(): string
            {
                return 'recording';
            }
        };
    }
}
