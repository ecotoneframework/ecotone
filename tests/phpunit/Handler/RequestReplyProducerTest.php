<?php

namespace Messaging\Handler;

use Fixture\Handler\NoReplyMessageProducer;
use Fixture\Handler\Processor\ThrowExceptionMessageProcessor;
use Fixture\Handler\ReplyMessageProducer;
use Messaging\Channel\QueueChannel;
use Messaging\Config\InMemoryChannelResolver;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageDeliveryException;
use Messaging\MessagingTest;
use Messaging\Support\ErrorMessage;
use Messaging\Support\MessageBuilder;

/**
 * Class RequestReplyProducerTest
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequestReplyProducerTest extends MessagingTest
{
    public function test_processing_message_without_reply()
    {
        $messageProcessor = NoReplyMessageProducer::create();
        $requestReplyProducer = $this->createRequestReplyProducer($messageProcessor);

        $this->handleReplyWith($requestReplyProducer);
        $this->assertTrue($messageProcessor->wasCalled(), "Service was not called");
    }

    /**
     * @param \Messaging\Handler\MessageProcessor $replyMessageProducer
     * @param MessageChannel|null $messageChannel
     * @param bool $requireReply
     * @return \Messaging\Handler\RequestReplyProducer
     */
    private function createRequestReplyProducer(MessageProcessor $replyMessageProducer, MessageChannel $messageChannel = null, bool $requireReply = false): RequestReplyProducer
    {
        return RequestReplyProducer::createFrom($messageChannel, $replyMessageProducer, InMemoryChannelResolver::createEmpty(), $requireReply);
    }

    /**
     * @param $requestReplyProducer
     */
    private function handleReplyWith(RequestReplyProducer $requestReplyProducer): void
    {
        $this->handleReplyWithMessage(
            MessageBuilder::withPayload('a')->build(),
            $requestReplyProducer
        );
    }

    /**
     * @param Message $message
     * @param RequestReplyProducer $requestReplyProducer
     */
    private function handleReplyWithMessage(Message $message, RequestReplyProducer $requestReplyProducer): void
    {
        $requestReplyProducer->handleWithReply($message);
    }

    public function test_processing_message_with_reply()
    {
        $outputChannel = QueueChannel::create();
        $replyData = "some result";
        $requestReplyProducer = $this->createRequestReplyProducer(ReplyMessageProducer::create($replyData), $outputChannel);

        $this->handleReplyWith($requestReplyProducer);

        $this->assertMessages(MessageBuilder::withPayload($replyData)->build(), $outputChannel->receive());
    }

    public function test_throwing_exception_if_required_reply_and_got_none()
    {
        $requestReplyProducer = $this->createRequestReplyProducer(NoReplyMessageProducer::create(), null, true);

        $this->expectException(MessageDeliveryException::class);

        $this->handleReplyWith($requestReplyProducer);
    }

    public function test_sending_reply_to_message_channel_if_there_is_nonone_in_producer()
    {
        $replyData = "some result";
        $requestReplyProducer = $this->createRequestReplyProducer(ReplyMessageProducer::create($replyData));

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('a')
            ->setReplyChannel($replyChannel)
            ->build();

        $this->handleReplyWithMessage($message, $requestReplyProducer);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($replyData)
                ->build(),
            $replyChannel->receive()
        );
    }

    public function test_throwing_exception_if_there_is_reply_data_but_no_output_channel()
    {
        $requestReplyProducer = $this->createRequestReplyProducer(ReplyMessageProducer::create("some payload"));

        $this->expectException(MessageDeliveryException::class);

        $this->handleReplyWith($requestReplyProducer);
    }

    public function test_propagating_message_headers()
    {
        $outputChannel = QueueChannel::create();
        $replyData = "some result";
        $requestReplyProducer = $this->createRequestReplyProducer(ReplyMessageProducer::create($replyData), $outputChannel);

        $replyChannelFromMessage = QueueChannel::create();
        $this->handleReplyWithMessage(
            MessageBuilder::withPayload('some')
                ->setHeader('token', "abcd")
                ->setReplyChannel($replyChannelFromMessage)
                ->build(),
            $requestReplyProducer);

        $this->assertMessages(
            MessageBuilder::withPayload($replyData)
                ->setHeader('token', "abcd")
                ->setReplyChannel($replyChannelFromMessage)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_routing_to_error_channel_if_exception_has_been_thrown()
    {
        $errorChannel = QueueChannel::create();
        $this->handleReplyWithMessage(
            MessageBuilder::withPayload('some')
                ->build(),
            RequestReplyProducer::createFrom(QueueChannel::create(), ThrowExceptionMessageProcessor::create(new \InvalidArgumentException()), InMemoryChannelResolver::createFromAssociativeArray([
                "errorChannel" => $errorChannel
            ]), false)
        );

        $this->assertInstanceOf(ErrorMessage::class, $errorChannel->receive());
    }
}