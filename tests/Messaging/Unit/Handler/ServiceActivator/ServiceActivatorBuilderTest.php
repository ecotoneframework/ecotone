<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\StaticallyCalledService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class ServiceActivatorBuilderTest
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class ServiceActivatorBuilderTest extends MessagingTest
{
    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_building_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue'));

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_handler_returns_message_with_no_reply_channel_and_making_use_of_requested_reply_channel()
    {
        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('some')
                    ->build();
        $objectToInvoke = ServiceReturningMessage::createWith($message);

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'get'));

        $serviceActivator->handle(MessageBuilder::withPayload('someOther')->setReplyChannel($replyChannel)->build());

        $this->assertNotNull($replyChannel->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_activating_statically_called_service()
    {
        $reference = StaticallyCalledService::class;

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::create($reference, InterfaceToCall::create($reference, 'run')));

        $payload = 'Hello World';
        $replyChannel = QueueChannel::create();
        $serviceActivator->handle(
            MessageBuilder::withPayload($payload)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $payload,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_calling_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue'));

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_through_on_void()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
            ->withPassThroughMessageOnVoidInterface(true));

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('test')
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $this->assertMessages(
            $message,
            $replyChannel->receive()
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_ignoring_passing_through_when_service_not_void()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withReturnValue')
            ->withPassThroughMessageOnVoidInterface(true));

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('test')
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $receivedMessage = $replyChannel->receive();

        $this->assertNotNull($receivedMessage);
        $this->assertNotEquals($message, $receivedMessage);
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_interceptors()
    {
        $objectToInvoke = CalculatingService::create(0);

        $firstInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 1, '', []);
        $secondInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'multiply'), 2, '', []);
        $thirdInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 3, '', []);
        $replyChannel = QueueChannel::create();

        $serviceActivator = ComponentTestBuilder::create()
            ->withReference('calculator', CalculatingServiceInterceptorExample::create(2))
            ->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'result')
                            ->withInputChannelName('someName')
                            ->withEndpointId('someEndpoint')
                            ->addAroundInterceptor($secondInterceptor)
                            ->addAroundInterceptor($thirdInterceptor)
                            ->addAroundInterceptor($firstInterceptor));

        $serviceActivator->handle(MessageBuilder::withPayload(1)->setReplyChannel($replyChannel)->build());

        $this->assertEquals(
            8,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_returning_array_from_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withArrayReturnValue'));

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('test')
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $receivedMessage = $replyChannel->receive();

        $this->assertNotNull($receivedMessage);
        $this->assertEquals(['some' => 'test'], $receivedMessage->getPayload());
        $this->assertArrayNotHasKey('some', $receivedMessage->getHeaders()->headers());
    }

    public function test_returning_array_and_changing_headers_from_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ComponentTestBuilder::create()->build(
            ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withArrayReturnValue')
                ->withChangingHeaders(true)
        );

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('test')
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $receivedMessage = $replyChannel->receive();

        $this->assertNotNull($receivedMessage);
        $this->assertEquals('test', $receivedMessage->getPayload());
        $this->assertArrayHasKey('some', $receivedMessage->getHeaders()->headers());
        $this->assertEquals('test', $receivedMessage->getHeaders()->get('some'));
    }
}
