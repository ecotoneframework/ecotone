<?php

namespace Messaging\Handler\ServiceActivator;

use Fixture\Handler\NoReplyMessageProducer;
use Fixture\Handler\ReplyMessageProducer;
use Messaging\Channel\QueueChannel;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageDeliveryException;
use Messaging\MessagingTest;
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
        $requestReplyProducer = $this->createRequestReplyProducer();

        $messageProcessor = NoReplyMessageProducer::create();
        $this->handleReplyWith($requestReplyProducer, $messageProcessor);

        $this->assertTrue($messageProcessor->wasCalled(), "Service was not called");
    }

    /**
     * @param MessageChannel $messageChannel
     * @param bool $requireReply
     * @return \Messaging\Handler\ServiceActivator\RequestReplyProducer
     */
    private function createRequestReplyProducer(MessageChannel $messageChannel = null, bool $requireReply = false): RequestReplyProducer
    {
        return new RequestReplyProducer($messageChannel, $requireReply);
    }

    /**
     * @param $requestReplyProducer
     * @param $messageProcessor
     */
    private function handleReplyWith(RequestReplyProducer $requestReplyProducer, MessageProcessor $messageProcessor): void
    {
        $this->handleReplyWithMessage(
            MessageBuilder::withPayload('a')->build(),
            $requestReplyProducer,
            $messageProcessor
        );
    }

    /**
     * @param Message $message
     * @param RequestReplyProducer $requestReplyProducer
     * @param MessageProcessor $messageProcessor
     */
    private function handleReplyWithMessage(Message $message, RequestReplyProducer $requestReplyProducer, MessageProcessor $messageProcessor): void
    {
        $requestReplyProducer->handleWithReply(
            $message,
            $messageProcessor
        );
    }

    public function test_processing_message_with_reply()
    {
        $outputChannel = new QueueChannel();
        $requestReplyProducer = $this->createRequestReplyProducer($outputChannel);

        $replyData = "some result";
        $messageProcessor = ReplyMessageProducer::create($replyData);
        $this->handleReplyWith($requestReplyProducer, $messageProcessor);

        $this->assertMessages(MessageBuilder::withPayload($replyData)->build(), $outputChannel->receive());
    }

    public function test_throwing_exception_if_required_reply_and_got_none()
    {
        $requestReplyProducer = $this->createRequestReplyProducer(null, true);

        $this->expectException(MessageDeliveryException::class);

        $messageProcessor = NoReplyMessageProducer::create();
        $this->handleReplyWith($requestReplyProducer, $messageProcessor);
    }

    public function test_sending_reply_to_message_channel_if_there_is_nonone_in_producer()
    {
        $requestReplyProducer = $this->createRequestReplyProducer();
        $replyChannel = new QueueChannel();
        $message = MessageBuilder::withPayload('a')
            ->setReplyChannel($replyChannel)
            ->build();

        $replyData = "some result";
        $messageProcessor = ReplyMessageProducer::create($replyData);
        $this->handleReplyWithMessage($message, $requestReplyProducer, $messageProcessor);

        $this->assertMessages(MessageBuilder::fromMessage($message)
            ->setPayload($replyData)
            ->build(), $replyChannel->receive());
    }

    public function test_throwing_exception_if_there_is_reply_data_but_no_output_channel()
    {
        $requestReplyProducer = $this->createRequestReplyProducer();

        $this->expectException(MessageDeliveryException::class);

        $messageProcessor = ReplyMessageProducer::create("some payload");
        $this->handleReplyWith($requestReplyProducer, $messageProcessor);
    }

    public function test_propagating_message_headers()
    {
        $outputChannel = new QueueChannel();
        $requestReplyProducer = $this->createRequestReplyProducer($outputChannel);

        $replyData = "some result";
        $messageProcessor = ReplyMessageProducer::create($replyData);
        $replyChannelFromMessage = new QueueChannel();
        $this->handleReplyWithMessage(
            MessageBuilder::withPayload('some')
                ->setHeader('token', "abcd")
                ->setReplyChannel($replyChannelFromMessage)
                ->build(),
            $requestReplyProducer, $messageProcessor);

        $this->assertMessages(
            MessageBuilder::withPayload($replyData)
                ->setHeader('token', "abcd")
                ->setReplyChannel($replyChannelFromMessage)
                ->build(),
            $outputChannel->receive()
        );
    }
}