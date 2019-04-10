<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\NoReplyMessageProducer;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\ThrowExceptionMessageProcessor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\FakeReplyMessageProducer;
use Prophecy\Argument;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageDeliveryException;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class RequestReplyProducerTest
 * @package SimplyCodedSoftware\Messaging\Handler
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
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create($replyData), $outputChannel);

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
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create($replyData));

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
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create("some payload"));

        $this->expectException(MessageDeliveryException::class);

        $this->handleReplyWith($requestReplyProducer);
    }

    public function test_propagating_message_headers()
    {
        $outputChannel = QueueChannel::create();
        $replyData = "some result";
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create($replyData), $outputChannel);

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

    public function test_splitting_payload_into_multiple_messages()
    {
        $replyData = [1, 2, 3, 4];
        $requestReplyProducer = RequestReplyProducer::createRequestAndSplit(null, FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $outputChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload('some')
            ->setHeader('token', "abcd")
            ->setReplyChannel($outputChannel)
            ->build();
        $requestReplyProducer->handleWithReply($requestMessage);

        $this->compareToSplittedMessages($replyData, $outputChannel, $requestMessage);
    }

    public function test_splitting_messages_when_multiple_messages_were_returned_from_service()
    {
        $replyData = [MessageBuilder::withPayload("some1")->build(), MessageBuilder::withPayload("some2")->build()];
        $requestReplyProducer = RequestReplyProducer::createRequestAndSplit(null, FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $outputChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload('some')
            ->setHeader('token', "abcd")
            ->setReplyChannel($outputChannel)
            ->build();
        $requestReplyProducer->handleWithReply($requestMessage);

        $splittedMessages = [];
        while ($splittedMessage = $outputChannel->receive()) {
            $splittedMessages[] = $splittedMessage;
        }

        $this->assertMultipleMessages(
            [
                MessageBuilder::withPayload("some2")
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $splittedMessages[0]->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID))
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, 2)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, 2)
                    ->build(),
                MessageBuilder::withPayload("some1")
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $splittedMessages[0]->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID))
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, 2)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, 1)
                    ->build(),
            ],
            $splittedMessages
        );
    }

    public function test_throwing_exception_if_result_of_service_call_is_not_array()
    {
        $replyData = "someString";
        $requestReplyProducer = RequestReplyProducer::createRequestAndSplit(null, FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $this->expectException(MessageDeliveryException::class);

        $requestReplyProducer->handleWithReply(
            MessageBuilder::withPayload('some')
                ->setReplyChannel(QueueChannel::create())
                ->build()
        );
    }

    /**
     * @param \SimplyCodedSoftware\Messaging\Handler\MessageProcessor $replyMessageProducer
     * @param MessageChannel|null $outputChannel
     * @param bool $requireReply
     * @return \SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer
     */
    private function createRequestReplyProducer(MessageProcessor $replyMessageProducer, MessageChannel $outputChannel = null, bool $requireReply = false): RequestReplyProducer
    {
        $outputChannelName = $outputChannel ? "output-channel" : "";
        $channelResolver = $outputChannel ? InMemoryChannelResolver::createFromAssociativeArray([
            $outputChannelName => $outputChannel
        ]) : InMemoryChannelResolver::createEmpty();

        return RequestReplyProducer::createRequestAndReply($outputChannelName, $replyMessageProducer, $channelResolver, $requireReply);
    }

    /**
     * @param $replyData
     * @param $outputChannel
     * @param $requestMessage
     */
    private function compareToSplittedMessages(array $replyData, PollableChannel $outputChannel, Message $requestMessage): void
    {
        $correlationHeader = null;
        $sequenceSize = count($replyData);
        for ($sequenceNumber = $sequenceSize - 1; $sequenceNumber >= 0; $sequenceNumber--) {
            $splittedMessage = $outputChannel->receive();

            $this->assertNotInstanceOf(Message::class, $splittedMessage->getPayload(), "Message is inside message.");
            if (!$correlationHeader) {
                $correlationHeader = $splittedMessage->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID);
            }

            $this->assertMessages(
                MessageBuilder::fromMessage($requestMessage)
                    ->setPayload($replyData[$sequenceNumber])
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationHeader)
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                    ->build(),
                $splittedMessage
            );
        }
    }
}