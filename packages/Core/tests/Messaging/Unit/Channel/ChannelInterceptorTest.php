<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Handler\ExceptionMessageHandler;

/**
 * Class ChannelInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChannelInterceptorTest extends TestCase
{
    public function test_intercepting_sending_with_sucess()
    {
        $requestMessage = MessageBuilder::withPayload("some1")->build();
        $transformedMessage = MessageBuilder::withPayload("some2")->build();
        $queueChannel = QueueChannel::create();

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preSend")
            ->willReturn($transformedMessage);
        $channelInterceptor
            ->expects($this->once())
            ->method("postSend")
            ->with($transformedMessage, $queueChannel);
        $channelInterceptor
            ->expects($this->once())
            ->method("afterSendCompletion")
            ->with($transformedMessage, $queueChannel, null);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);
    }

    public function test_intercepting_to_not_send_the_request_message()
    {
        $requestMessage = MessageBuilder::withPayload("some1")->build();
        $queueChannel = QueueChannel::create();

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preSend")
            ->willReturn(null);
        $channelInterceptor
            ->expects($this->never())
            ->method("postSend");

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);
    }

    public function test_intercepting_send_completion_if_exception_occurred()
    {
        $requestMessage = MessageBuilder::withPayload("some1")->build();
        $queueChannel = $this->createMock(QueueChannel::class);
        $queueChannel
            ->method("send")
            ->willThrowException(new \InvalidArgumentException())
            ->with($requestMessage);

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preSend")
            ->willReturn($requestMessage);
        $channelInterceptor
            ->expects($this->once())
            ->method("afterSendCompletion")
            ->with($requestMessage, $queueChannel, new \InvalidArgumentException(""));

        $this->expectException(\InvalidArgumentException::class);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);
    }

    public function test_intercepting_receiving_message_with_success()
    {
        $message = MessageBuilder::withPayload("some1")->build();
        $queueChannel = QueueChannel::create();
        $queueChannel->send($message);

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preReceive")
            ->with($queueChannel)
            ->willReturn(true);
        $channelInterceptor
            ->expects($this->once())
            ->method("postReceive")
            ->with($message, $queueChannel);
        $channelInterceptor
            ->expects($this->once())
            ->method("afterReceiveCompletion")
            ->with($message, $queueChannel, null);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();
    }

    public function test_stopping_message_receiving()
    {
        $message = MessageBuilder::withPayload("some1")->build();
        $queueChannel = QueueChannel::create();
        $queueChannel->send($message);

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preReceive")
            ->with($queueChannel)
            ->willReturn(false);
        $channelInterceptor
            ->expects($this->never())
            ->method("postReceive");

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();
    }

    public function test_intercepting_when_exception_occurrs()
    {
        $queueChannel = $this->createMock(QueueChannel::class);
        $queueChannel
            ->method("receive")
            ->willThrowException(new \InvalidArgumentException());

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor
            ->method("preReceive")
            ->with($queueChannel)
            ->willReturn(true);
        $channelInterceptor
            ->expects($this->once())
            ->method("afterReceiveCompletion")
            ->with(null, $queueChannel, new \InvalidArgumentException(""));

        $this->expectException(\InvalidArgumentException::class);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();
    }
}