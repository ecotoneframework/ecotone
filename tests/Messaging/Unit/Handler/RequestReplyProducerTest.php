<?php

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Handler\Splitter\SplitterHandler;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Handler\FakeReplyMessageProducer;
use Test\Ecotone\Messaging\Fixture\Handler\NoReplyMessageProducer;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class RequestReplyProducerTest
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class RequestReplyProducerTest extends MessagingTestCase
{
    public function test_processing_message_without_reply()
    {
        $messageProcessor = NoReplyMessageProducer::create();
        $requestReplyProducer = $this->createRequestReplyProducer($messageProcessor);

        $this->handleReplyWith($requestReplyProducer);
        $this->assertTrue($messageProcessor->wasCalled(), 'Service was not called');
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

    private function handleReplyWithMessage(Message $message, RequestReplyProducer $requestReplyProducer): void
    {
        $requestReplyProducer->handle($message);
    }

    public function test_processing_message_with_reply()
    {
        $outputChannel = QueueChannel::create();
        $replyData = 'some result';
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create($replyData), $outputChannel);

        $this->handleReplyWith($requestReplyProducer);

        $this->assertSame($replyData, $outputChannel->receive()->getPayload());
    }

    public function test_throwing_exception_if_required_reply_and_got_none()
    {
        $requestReplyProducer = $this->createRequestReplyProducer(NoReplyMessageProducer::create(), null, true);

        $this->expectException(MessageDeliveryException::class);

        $this->handleReplyWith($requestReplyProducer);
    }

    public function test_sending_reply_to_message_channel_if_there_is_nonone_in_producer()
    {
        $replyData = 'some result';
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

    /**
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Handler\DestinationResolutionException
     */
    public function test_passing_message_to_next_channel_if_defined_in_routing_slip()
    {
        $replyData = 'some result';
        $replyChannelName = 'reply';
        $replyChannel = QueueChannel::create();
        $requestReplyProducer = $this->createRequestReplyProducerWithChannels(
            FakeReplyMessageProducer::create($replyData),
            [$replyChannelName => $replyChannel],
            null
        );

        $message = MessageBuilder::withPayload('a')
            ->setHeader(MessageHeaders::ROUTING_SLIP, $replyChannelName)
            ->build();

        $this->handleReplyWithMessage($message, $requestReplyProducer);

        $this->assertEquals(
            MessageBuilder::fromMessage($message)
                ->setPayload($replyData)
                ->removeHeader(MessageHeaders::ROUTING_SLIP)
                ->build(),
            $replyChannel->receive()
        );
    }

    /**
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Handler\DestinationResolutionException
     */
    public function test_passing_message_to_next_routing_slip()
    {
        $replyData = 'some result';
        $replyChannelName = 'reply1,reply2';
        $replyChannel1 = QueueChannel::create();
        $replyChannel2 = QueueChannel::create();
        $requestReplyProducer = $this->createRequestReplyProducerWithChannels(
            FakeReplyMessageProducer::create($replyData),
            [
                'reply1' => $replyChannel1,
                'reply2' => $replyChannel2,
            ],
            null
        );

        $message = MessageBuilder::withPayload('a')
            ->setHeader(MessageHeaders::ROUTING_SLIP, $replyChannelName)
            ->build();

        $this->handleReplyWithMessage($message, $requestReplyProducer);
        $message = $replyChannel1->receive();
        $this->handleReplyWithMessage($message, $requestReplyProducer);
        $this->assertNotNull($replyChannel2->receive());
    }

    /**
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Handler\DestinationResolutionException
     */
    public function test_passing_message_to_next_routing_slips()
    {
        $replyData = 'some result';
        $replyChannel1 = QueueChannel::create();
        $replyChannel2 = QueueChannel::create();
        $requestReplyProducer = $this->createRequestReplyProducerWithChannels(
            FakeReplyMessageProducer::create($replyData),
            [
                'reply1' => $replyChannel1,
                'reply2' => $replyChannel2,
            ],
            null
        );

        $message = MessageBuilder::withPayload('a')
            ->setHeader(MessageHeaders::ROUTING_SLIP, 'reply1,reply2')
            ->build();

        $this->handleReplyWithMessage($message, $requestReplyProducer);
        $message = $replyChannel1->receive();
        $this->handleReplyWithMessage($message, $requestReplyProducer);
        $this->assertNotNull($replyChannel2->receive());
    }

    public function test_propagating_message_headers()
    {
        $outputChannel = QueueChannel::create();
        $replyData = 'some result';
        $requestReplyProducer = $this->createRequestReplyProducer(FakeReplyMessageProducer::create($replyData), $outputChannel);

        $replyChannelFromMessage = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload('some')
            ->setHeader('token', 'abcd')
            ->setReplyChannel($replyChannelFromMessage)
            ->build();

        $this->handleReplyWithMessage($requestMessage, $requestReplyProducer);

        $this->assertEquals(
            MessageBuilder::fromMessage($requestMessage)
                ->setPayload($replyData)
                ->setHeader('token', 'abcd')
                ->setReplyChannel($replyChannelFromMessage)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_splitting_payload_into_multiple_messages()
    {
        $replyData = [1, 2, 3, 4];
        $outputChannel = QueueChannel::create();
        $requestReplyProducer = new SplitterHandler($outputChannel, FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $requestMessage = MessageBuilder::withPayload('some')
            ->setHeader('token', 'abcd')
            ->setReplyChannel($outputChannel)
            ->build();
        $requestReplyProducer->handle($requestMessage);

        $this->compareToSplittedMessages($replyData, $outputChannel, $requestMessage);
    }

    public function test_splitting_messages_when_multiple_messages_were_returned_from_service()
    {
        $replyData = [MessageBuilder::withPayload('some1')->build(), MessageBuilder::withPayload('some2')->build()];
        $outputChannel = QueueChannel::create();
        $requestReplyProducer = new SplitterHandler($outputChannel, FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $requestMessage = MessageBuilder::withPayload('some')
            ->setHeader('token', 'abcd')
            ->build();
        $requestReplyProducer->handle($requestMessage);

        /** @var Message[] $splittedMessages */
        $splittedMessages = [];
        while ($splittedMessage = $outputChannel->receive()) {
            $splittedMessages[] = $splittedMessage;
        }

        $this->assertMultipleMessages(
            [
                MessageBuilder::withPayload('some1')
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $requestMessage->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID))
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, 2)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, 1)
                    ->build(),
                MessageBuilder::withPayload('some2')
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $requestMessage->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID))
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, 2)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, 2)
                    ->build(),
            ],
            $splittedMessages
        );

        $this->assertNotSame($splittedMessages[0]->getHeaders()->getMessageId(), $splittedMessages[1]->getHeaders()->getMessageId());
    }

    public function test_throwing_exception_if_result_of_service_call_is_not_array()
    {
        $replyData = 'someString';
        $requestReplyProducer = new SplitterHandler(QueueChannel::create(), FakeReplyMessageProducer::create($replyData), InMemoryChannelResolver::createEmpty());

        $this->expectException(MessageDeliveryException::class);

        $requestReplyProducer->handle(
            MessageBuilder::withPayload('some')
                ->build()
        );
    }

    private function createRequestReplyProducer(MessageProcessor $replyMessageProducer, ?MessageChannel $outputChannel = null, bool $requireReply = false): RequestReplyProducer
    {
        $outputChannelName = $outputChannel ? 'output-channel' : '';
        $channelResolver = $outputChannel ? InMemoryChannelResolver::createFromAssociativeArray([
            $outputChannelName => $outputChannel,
        ]) : InMemoryChannelResolver::createEmpty();

        return new RequestReplyProducer(
            $outputChannel,
            $replyMessageProducer,
            $channelResolver,
            $requireReply
        );
    }

    /**
     * @param array<string, MessageChannel> $messageChannels
     */
    private function createRequestReplyProducerWithChannels(MessageProcessor $replyMessageProducer, array $messageChannels, ?string $outputChannelName): RequestReplyProducer
    {
        $outputChannel = $outputChannelName ? $messageChannels[$outputChannelName] : null;
        return new RequestReplyProducer(
            $outputChannel,
            $replyMessageProducer,
            InMemoryChannelResolver::createFromAssociativeArray($messageChannels),
            false,
        );
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

        $messageIds = [];
        for ($sequenceNumber = 0; $sequenceNumber < $sequenceSize; $sequenceNumber++) {
            $splittedMessage = $outputChannel->receive();
            $messageIds[] = $splittedMessage->getHeaders()->getMessageId();

            $this->assertNotInstanceOf(Message::class, $splittedMessage->getPayload(), 'Message is inside message.');
            if (! $correlationHeader) {
                $correlationHeader = $splittedMessage->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID);
            }

            $this->assertMessages(
                MessageBuilder::fromMessage($requestMessage)
                    ->setPayload($replyData[$sequenceNumber])
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationHeader)
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($replyData[$sequenceNumber])->toString()))
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                    ->build(),
                $splittedMessage
            );
        }

        $this->assertCount(4, array_unique($messageIds));
    }
}
