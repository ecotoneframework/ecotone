<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class ChannelInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ChannelInterceptorTest extends TestCase
{
    public function test_intercepting_sending_with_sucess()
    {
        $requestMessage = MessageBuilder::withPayload('some1')->build();
        $transformedMessage = MessageBuilder::withPayload('some2')->build();
        $queueChannel = QueueChannel::create();

        $channelInterceptor = new TestChannelInterceptor($transformedMessage);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertTrue($channelInterceptor->wasPostSendCalled());
        $this->assertTrue($channelInterceptor->wasAfterSendCompletionCalled());
        $this->assertSame($transformedMessage, $channelInterceptor->getCapturedMessage());
        $this->assertSame($queueChannel, $channelInterceptor->getCapturedChannel());
        $this->assertNull($channelInterceptor->getCapturedException());
    }

    public function test_intercepting_to_not_send_the_request_message()
    {
        $requestMessage = MessageBuilder::withPayload('some1')->build();
        $queueChannel = QueueChannel::create();

        $channelInterceptor = new TestChannelInterceptor(null, true, false, null);
        $channelInterceptor->setReturnNullOnPreSend(true);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertFalse($channelInterceptor->wasPostSendCalled());
        $this->assertFalse($channelInterceptor->wasAfterSendCompletionCalled());
        $this->assertSame($requestMessage, $channelInterceptor->getCapturedMessage());
    }

    public function test_intercepting_send_completion_if_exception_occurred()
    {
        $requestMessage = MessageBuilder::withPayload('some1')->build();
        $queueChannel = TestQueueChannel::createWithException();

        $channelInterceptor = new TestChannelInterceptor($requestMessage, true, false);

        $this->expectException(InvalidArgumentException::class);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);
    }

    public function test_intercepting_send_completion_if_exception_occurred_and_was_handled()
    {
        $requestMessage = MessageBuilder::withPayload('some1')->build();
        $queueChannel = TestQueueChannel::createWithException();

        $channelInterceptor = new TestChannelInterceptor($requestMessage, true, true);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->send($requestMessage);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertTrue($channelInterceptor->wasPostSendCalled());
        $this->assertTrue($channelInterceptor->wasAfterSendCompletionCalled());
        $this->assertSame($requestMessage, $channelInterceptor->getCapturedMessage());
        $this->assertSame($queueChannel, $channelInterceptor->getCapturedChannel());
        $this->assertInstanceOf(InvalidArgumentException::class, $channelInterceptor->getCapturedException());
    }

    public function test_intercepting_receiving_message_with_success()
    {
        $message = MessageBuilder::withPayload('some1')->build();
        $queueChannel = QueueChannel::create();
        $queueChannel->send($message);

        $channelInterceptor = new TestChannelInterceptor(null, true, false, null);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();

        $this->assertTrue($channelInterceptor->wasPreReceiveCalled());
        $this->assertTrue($channelInterceptor->wasPostReceiveCalled());
        $this->assertTrue($channelInterceptor->wasAfterReceiveCompletionCalled());
        $this->assertSame($message, $channelInterceptor->getCapturedMessage());
        $this->assertSame($queueChannel, $channelInterceptor->getCapturedChannel());
        $this->assertNull($channelInterceptor->getCapturedException());
    }

    public function test_stopping_message_receiving()
    {
        $message = MessageBuilder::withPayload('some1')->build();
        $queueChannel = QueueChannel::create();
        $queueChannel->send($message);

        $channelInterceptor = new TestChannelInterceptor(null, false);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();

        $this->assertTrue($channelInterceptor->wasPreReceiveCalled());
        $this->assertFalse($channelInterceptor->wasPostReceiveCalled());
        $this->assertFalse($channelInterceptor->wasAfterReceiveCompletionCalled());
        $this->assertNull($channelInterceptor->getCapturedMessage());
        $this->assertSame($queueChannel, $channelInterceptor->getCapturedChannel());
    }

    public function test_intercepting_when_exception_occurrs()
    {
        $queueChannel = TestQueueChannel::createWithException();

        $channelInterceptor = new TestChannelInterceptor();

        $this->expectException(InvalidArgumentException::class);

        $pollableChannel = new PollableChannelInterceptorAdapter(
            $queueChannel,
            [$channelInterceptor]
        );
        $pollableChannel->receive();
    }
}
