<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\DeliveryConfirmation\FailedDelivery;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PublishingFailedException;
use Ecotone\Messaging\Channel\PollableChannel\SendRetries\SendRetryChannelInterceptor;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Scheduling\StubUTCClock;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
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

        $batchMessage = MessageBuilder::withPayload(
            BatchMessage::constructEmpty()
                ->append('first delivered order')
                ->append('poison order')
                ->append('second delivered order')
        )->build();

        $interceptor->afterSendCompletion(
            $batchMessage,
            $channel,
            PublishingFailedException::withFailedDeliveries([
                new FailedDelivery(MessageBuilder::withPayload('poison order')->build(), 'nacked by broker', 'orders'),
            ]),
        );

        $this->assertSame([['poison order']], $channel->sentBatchPayloads);
    }

    public function test_retry_of_single_message_failure_redelivers_original_message(): void
    {
        $channel = $this->createRecordingChannel();
        $interceptor = $this->createInterceptor();

        $singleMessage = MessageBuilder::withPayload('failed order')->build();

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
        return new SendRetryChannelInterceptor(
            'orders',
            RetryTemplateBuilder::fixedBackOff(1)->maxRetryAttempts(1)->build(),
            null,
            new ErrorChannelService(
                new LoggingService(),
                $this->createStub(OutboundMessageConverter::class),
                $this->createStub(ConversionService::class),
                new MessageHeadersPropagatorInterceptor(),
            ),
            $this->createStub(ConfiguredMessagingSystem::class),
            new NullLogger(),
            StubUTCClock::createWithCurrentTime('2025-01-01 00:00:00'),
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
                    ? array_map(fn (array $entry): mixed => $entry['payload'], $payload->getEntries())
                    : [$payload];
            }
        };
    }
}
