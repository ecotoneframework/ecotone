<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
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
        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
                                ->build(
                                    InMemoryChannelResolver::createEmpty(),
                                    InMemoryReferenceSearchService::createEmpty()
                                );

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

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'get')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

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

        $serviceActivator = ServiceActivatorBuilder::create($reference, InterfaceToCall::create($reference, 'run'))
                                ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());


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

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

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

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
            ->withPassThroughMessageOnVoidInterface(true)
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );


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

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withReturnValue')
            ->withPassThroughMessageOnVoidInterface(true)
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );


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

        $firstInterceptor = AroundInterceptorReference::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 1, '', []);
        $secondInterceptor = AroundInterceptorReference::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'multiply'), 2, '', []);
        $thirdInterceptor = AroundInterceptorReference::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 3, '', []);
        $replyChannel = QueueChannel::create();

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'result')
                            ->withInputChannelName('someName')
                            ->withEndpointId('someEndpoint')
                            ->addAroundInterceptor($secondInterceptor)
                            ->addAroundInterceptor($thirdInterceptor)
                            ->addAroundInterceptor($firstInterceptor)
                            ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                                'calculator' => CalculatingServiceInterceptorExample::create(2),
                            ]));

        $serviceActivator->handle(MessageBuilder::withPayload(1)->setReplyChannel($replyChannel)->build());

        $this->assertEquals(
            8,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_resolving_correct_interface_from_direct_object()
    {
        $objectToInvoke = CalculatingServiceInterceptorExample::create(0);
        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'result');

        $this->assertEquals(
            [
                InterfaceToCall::create($objectToInvoke, 'result'),
            ],
            $serviceActivator->resolveRelatedInterfaces(
                InterfaceToCallRegistry::createWith(InMemoryAnnotationFinder::createFrom([CalculatingServiceInterceptorExample::class])),
            )
        );
    }

    public function test_resolving_correct_interface_from_reference_object()
    {
        $objectToInvokeOnReference = 'service-a';
        $objectToInvoke = CalculatingServiceInterceptorExample::create(0);

        $serviceActivator = ServiceActivatorBuilder::create($objectToInvokeOnReference, InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'result'));

        $this->assertEquals(
            [
                InterfaceToCall::create($objectToInvoke, 'result'),
            ],
            $serviceActivator->resolveRelatedInterfaces(
                InterfaceToCallRegistry::createWith(
                    InMemoryAnnotationFinder::createFrom([CalculatingServiceInterceptorExample::class])
                )
            )
        );
    }
}
