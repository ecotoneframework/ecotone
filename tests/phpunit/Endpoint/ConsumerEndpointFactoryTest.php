<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use Fixture\Service\CalculatingService;
use Fixture\Service\ServiceExpectingOneArgument;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class ConsumerEndpointFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactoryTest extends MessagingTest
{
    private const INPUT_CHANNEL_NAME = "inputChannelName";

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_consumer_for_no_reply_service()
    {
        $inputChannel = DirectChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [];
        $postCallInterceptorBuilders = [];
        $noReplyService = ServiceExpectingOneArgument::create();
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference($noReplyService, "withoutReturnValue");

        $message = $this->buildMessage();
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $this->assertTrue($noReplyService->wasCalled());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_consumer_for_service_with_reply()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_consumer_with_pre_call_interceptor()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [
            Interceptor::create("handlerName", ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
        ];
        $postCallInterceptorBuilders = [];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
                            ->withName("handlerName");

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 3;
        $this->assertEquals(
            $expectedResult,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_omitting_interceptor_when_handler_name_is_different()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [
            Interceptor::create("someOtherName", ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
        ];
        $postCallInterceptorBuilders = [];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
            ->withName("handlerName");

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 2;
        $this->assertEquals(
            $expectedResult,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_intercepting_after_service_call()
    {
        $inputChannel = DirectChannel::create();
        $replyChannel = QueueChannel::create();
        $consumerBuilders = [new \SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder()];
        $preCallInterceptorBuilders = [
            Interceptor::create("handlerName", ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "sum"))
        ];
        $postCallInterceptorBuilders = [
            Interceptor::create("handlerName", ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"))
        ];
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "sum")
            ->withName("handlerName");

        $message = $this->buildMessageWithReplyChannel(0, $replyChannel);
        $this->createConsumerAndSendMessage($inputChannel, $consumerBuilders, $preCallInterceptorBuilders, $postCallInterceptorBuilders, $messageHandler, $message);

        $expectedResult = 6;
        $this->assertEquals(
            $expectedResult,
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
     * @return Message
     */
    private function buildMessage() : Message
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createConsumerAndSendMessage(MessageChannel $inputChannel, array $consumerBuilders, array $preCallInterceptorBuilders, array $postCallInterceptorBuilders, MessageHandlerBuilder $messageHandler, Message $message): void
    {
        $consumerEndpointFactory = new ConsumerEndpointFactory(InMemoryChannelResolver::createFromAssociativeArray([
            self::INPUT_CHANNEL_NAME => $inputChannel
        ]), InMemoryReferenceSearchService::createEmpty(),
            $consumerBuilders,
            $preCallInterceptorBuilders,
            $postCallInterceptorBuilders
        );

        $messageHandler = $messageHandler
            ->withInputChannelName(self::INPUT_CHANNEL_NAME);
        $consumer = $consumerEndpointFactory->createForMessageHandler($messageHandler);
        $consumer->start();
        $inputChannel->send($message);
    }
}