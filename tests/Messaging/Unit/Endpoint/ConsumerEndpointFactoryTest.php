<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class ConsumerEndpointFactoryTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactoryTest extends MessagingTest
{
    private const INPUT_CHANNEL_NAME = "inputChannelName";

    /**
     * @throws MessagingException
     */
    public function test_creating_consumer_for_no_reply_service()
    {
        $inputChannel = DirectChannel::create();
        $consumerBuilders = [new EventDrivenConsumerBuilder()];
        $noReplyService = ServiceExpectingOneArgument::create();
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference($noReplyService, "withoutReturnValue");

        $message = $this->buildMessage();
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $messageHandler, $message);

        $this->assertTrue($noReplyService->wasCalled());
    }

    /**
     * @return Message
     */
    private function buildMessage(): Message
    {
        return MessageBuilder::withPayload("some")
            ->build();
    }

    /**
     * @throws NoConsumerFactoryForBuilderException
     * @throws MessagingException
     */
    public function test_creating_consumer_for_service_with_reply()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new EventDrivenConsumerBuilder()];
        $firstValueForMathOperations = 0;
        $secondValueForMathOperations = 4;
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create($secondValueForMathOperations), "sum");

        $message = $this->buildMessageWithReplyChannel($firstValueForMathOperations, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $messageHandler, $message);

        $this->assertEquals(
            4,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws MessagingException
     * @throws NoConsumerFactoryForBuilderException
     */
    public function test_creating_poll_or_throw_consumer_with_limit_messages_interceptor()
    {
        $inputChannel = QueueChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new PollOrThrowMessageHandlerConsumerBuilder()];
        $firstValueForMathOperations = 0;
        $secondValueForMathOperations = 4;
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create($secondValueForMathOperations), "sum");

        $message = $this->buildMessageWithReplyChannel($firstValueForMathOperations, $replyChannel);

        $consumerEndpointFactory = new ConsumerEndpointFactory(
            InMemoryChannelResolver::createFromAssociativeArray([
                self::INPUT_CHANNEL_NAME => $inputChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $consumerBuilders,
            []
        );

        $messageHandler = $messageHandler
            ->withInputChannelName(self::INPUT_CHANNEL_NAME);
        $consumer = $consumerEndpointFactory->createForMessageHandler($messageHandler);
        $consumer->run();
        $inputChannel->send($message);

        $this->assertEquals(
            4,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @param MessageChannel $inputChannel
     * @param array $consumerBuilders
     * @param array $preCallInterceptorBuilders
     * @param array $postCallInterceptorBuilders
     * @param MessageHandlerBuilder $messageHandler
     * @param Message $message
     * @throws NoConsumerFactoryForBuilderException
     * @throws MessagingException
     */
    private function createConsumerAndSendMessage(MessageChannel $inputChannel, array $consumerBuilders, MessageHandlerBuilder $messageHandler, Message $message): void
    {
        $consumerEndpointFactory = new ConsumerEndpointFactory(
            InMemoryChannelResolver::createFromAssociativeArray([
                self::INPUT_CHANNEL_NAME => $inputChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $consumerBuilders,
            []
        );

        $messageHandler = $messageHandler
            ->withInputChannelName(self::INPUT_CHANNEL_NAME);
        $consumer = $consumerEndpointFactory->createForMessageHandler($messageHandler);
        $consumer->run();
        $inputChannel->send($message);
    }

    /**
     * @param $payload
     * @param MessageChannel $replyChannel
     * @return Message
     */
    private function buildMessageWithReplyChannel($payload, MessageChannel $replyChannel): Message
    {
        return MessageBuilder::withPayload($payload)
            ->setReplyChannel($replyChannel)
            ->build();
    }
}