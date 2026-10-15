<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RequestReplyAndRoutingSlipTest extends TestCase
{
    public function test_handler_without_output_channel_and_no_reply_produces_nothing(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertNull($ecotone->sendDirectToChannel(RequestReplyHandler::NO_REPLY_CHANNEL, 'a'));
    }

    public function test_handler_reply_is_routed_to_its_declared_output_channel(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendDirectToChannel(RequestReplyHandler::WITH_OUTPUT_CHANNEL, 'a');

        /** @var PollableChannel $outputChannel */
        $outputChannel = $ecotone->getMessageChannel(RequestReplyHandler::OUTPUT_CHANNEL);
        $this->assertSame('some result', $outputChannel->receive()->getPayload());
    }

    public function test_throws_when_reply_is_required_but_handler_returns_none(): void
    {
        $ecotone = $this->bootstrap();

        $this->expectException(MessageDeliveryException::class);

        $ecotone->sendDirectToChannel(RequestReplyHandler::REQUIRED_REPLY_CHANNEL, 'a');
    }

    public function test_reply_is_sent_to_the_channel_declared_on_the_request_message_when_no_static_output_channel(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(RequestReplyHandler::NO_OUTPUT_CHANNEL, 'a', [
            'token' => 'abcd',
        ]);

        $this->assertSame('some result', $message->getPayload());
        $this->assertSame('abcd', $message->getHeaders()->get('token'));
    }

    public function test_routing_slip_forwards_reply_to_the_next_channel_when_no_static_output_channel(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendDirectToChannel(RequestReplyHandler::NO_OUTPUT_CHANNEL, 'a', [
            MessageHeaders::ROUTING_SLIP => RequestReplyHandler::NEXT_CHANNEL,
        ]);

        /** @var PollableChannel $nextChannel */
        $nextChannel = $ecotone->getMessageChannel(RequestReplyHandler::NEXT_CHANNEL);
        $message = $nextChannel->receive();

        $this->assertNotNull($message);
        $this->assertSame('some result', $message->getPayload());
        $this->assertFalse($message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP), 'Fully consumed routing slip must be removed');
    }

    public function test_routing_slip_advances_to_the_second_hop_once_the_first_hop_message_is_replayed(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendDirectToChannel(RequestReplyHandler::NO_OUTPUT_CHANNEL, 'a', [
            MessageHeaders::ROUTING_SLIP => RequestReplyHandler::NEXT_CHANNEL . ',' . RequestReplyHandler::FINAL_CHANNEL,
        ]);

        /** @var PollableChannel $nextChannel */
        $nextChannel = $ecotone->getMessageChannel(RequestReplyHandler::NEXT_CHANNEL);
        $firstHopMessage = $nextChannel->receive();
        $this->assertNotNull($firstHopMessage);
        $this->assertTrue($firstHopMessage->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP), 'Second hop must remain on the routing slip');

        $ecotone->sendMessageDirectToChannel(
            RequestReplyHandler::NO_OUTPUT_CHANNEL,
            MessageBuilder::fromMessage($firstHopMessage)->removeHeader(MessagingEntrypointService::ENTRYPOINT)->build()
        );

        /** @var PollableChannel $finalChannel */
        $finalChannel = $ecotone->getMessageChannel(RequestReplyHandler::FINAL_CHANNEL);
        $this->assertNotNull($finalChannel->receive());
    }

    private function bootstrap()
    {
        return EcotoneLite::bootstrapFlowTesting(
            [RequestReplyHandler::class],
            [new RequestReplyHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel(RequestReplyHandler::OUTPUT_CHANNEL),
                    SimpleMessageChannelBuilder::createQueueChannel(RequestReplyHandler::NEXT_CHANNEL),
                    SimpleMessageChannelBuilder::createQueueChannel(RequestReplyHandler::FINAL_CHANNEL),
                ])
                    ->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RequestReplyHandler
{
    public const NO_REPLY_CHANNEL = 'requestReply.noReply';
    public const WITH_OUTPUT_CHANNEL = 'requestReply.withOutput';
    public const OUTPUT_CHANNEL = 'requestReply.output';
    public const REQUIRED_REPLY_CHANNEL = 'requestReply.requiredReply';
    public const NO_OUTPUT_CHANNEL = 'requestReply.noOutput';
    public const NEXT_CHANNEL = 'requestReply.next';
    public const FINAL_CHANNEL = 'requestReply.final';

    #[InternalHandler(self::NO_REPLY_CHANNEL)]
    public function noReply(string $payload): void
    {
    }

    #[InternalHandler(self::WITH_OUTPUT_CHANNEL, outputChannelName: self::OUTPUT_CHANNEL)]
    public function withOutputChannel(string $payload): string
    {
        return 'some result';
    }

    #[InternalHandler(self::REQUIRED_REPLY_CHANNEL, requiresReply: true)]
    public function requiredReplyButNoneProduced(string $payload): void
    {
    }

    #[InternalHandler(self::NO_OUTPUT_CHANNEL)]
    public function noStaticOutputChannel(string $payload): string
    {
        return 'some result';
    }
}
