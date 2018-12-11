<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_consumer_for_no_reply_service()
    {
        $inputChannel = DirectChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [];
        $postCallInterceptorBuilders = [];
        $noReplyService = ServiceExpectingOneArgument::create();
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference($noReplyService, "withoutReturnValue");

        $message = $this->buildMessage();
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

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
     * @param MessageChannel $inputChannel
     * @param array $consumerBuilders
     * @param array $preCallInterceptorBuilders
     * @param array $postCallInterceptorBuilders
     * @param MessageHandlerBuilder $messageHandler
     * @param Message $message
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createConsumerAndSendMessage(MessageChannel $inputChannel, array $consumerBuilders, array $preCallInterceptorBuilders, array $postCallInterceptorBuilders, MessageHandlerBuilder $messageHandler, Message $message): void
    {
        $consumerEndpointFactory = new ConsumerEndpointFactory(
            InMemoryChannelResolver::createFromAssociativeArray([
                self::INPUT_CHANNEL_NAME => $inputChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $consumerBuilders,
            $preCallInterceptorBuilders,
            $postCallInterceptorBuilders,
            []
        );

        $messageHandler = $messageHandler
            ->withInputChannelName(self::INPUT_CHANNEL_NAME);
        $consumer = $consumerEndpointFactory->createForMessageHandler($messageHandler);
        $consumer->start();
        $inputChannel->send($message);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_consumer_for_service_with_reply()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [];
        $postCallInterceptorBuilders = [];
        $firstValueForMathOperations = 0;
        $secondValueForMathOperations = 4;
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create($secondValueForMathOperations), "sum");

        $message = $this->buildMessageWithReplyChannel($firstValueForMathOperations, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $this->assertEquals(
            4,
            $replyChannel->receive()->getPayload()
        );
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

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_consumer_with_pre_call_interceptor()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $endpointName = "handlerName";
        $preCallInterceptorBuilders = [
            ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                ->withEndpointId($endpointName)
        ];
        $postCallInterceptorBuilders = [];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
            ->withEndpointId($endpointName);

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 3;
        $this->assertEquals(
            $expectedResult,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_omitting_interceptor_when_handler_name_is_different()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [
            ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                ->withEndpointId("someOtherName")
        ];
        $postCallInterceptorBuilders = [];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
            ->withEndpointId("handlerName");

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 2;
        $this->assertEquals(
            $expectedResult,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_intercepting_after_service_call()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $messageHandlerName = "handlerName";
        $preCallInterceptorBuilders = [
            ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "sum")
                ->withEndpointId($messageHandlerName)
        ];
        $postCallInterceptorBuilders = [
            ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply")
                ->withEndpointId($messageHandlerName)
        ];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "sum")
            ->withEndpointId($messageHandlerName);

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 6;
        $this->assertEquals(
            $expectedResult,
            $replyChannel->receive()->getPayload()
        );
    }
}