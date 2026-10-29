<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\Lite\EcotoneLite;
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
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class ServiceActivatorBuilderTest
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ServiceActivatorBuilderTest extends MessagingTestCase
{
    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_building_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceExpectingOneArgument::class], [ServiceExpectingOneArgument::class => $objectToInvoke]);

        $ecotoneLite->sendDirectToChannel('withoutReturnValue', 'some');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_handler_returns_message_with_no_reply_channel_and_making_use_of_requested_reply_channel()
    {
        $message = MessageBuilder::withPayload('some')
                    ->build();

        $objectToInvoke = ServiceReturningMessage::createWith($message);
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceReturningMessage::class], [ServiceReturningMessage::class => $objectToInvoke]);

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('get', 'someOther')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_activating_statically_called_service()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([StaticallyCalledService::class]);

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('run', 'someOther')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_calling_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceExpectingOneArgument::class], [ServiceExpectingOneArgument::class => $objectToInvoke]);

        $ecotoneLite->sendDirectToChannel('withoutReturnValue', 'some');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_through_on_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withoutReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();

        // value is returned even so service is void
        $this->assertEquals(
            'test',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
        ;
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_ignoring_passing_through_when_service_not_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build(
            );

        $this->assertEquals(
            'test_called',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
        ;
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_interceptors()
    {
        $objectToInvoke = CalculatingService::create(0);
        $firstInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 1, CalculatingService::class . '::result');
        $secondInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'multiply'), 2, CalculatingService::class . '::result');
        $thirdInterceptor = AroundInterceptorBuilder::create('calculator', InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 3, CalculatingService::class . '::result');

        $messaging = ComponentTestBuilder::create()
            ->withReference('calculator', CalculatingServiceInterceptorExample::create(2))
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'result')
                    ->withInputChannelName('someName')
                    ->withEndpointId('someEndpoint')
            )
            ->withAroundInterceptor($secondInterceptor)
            ->withAroundInterceptor($thirdInterceptor)
            ->withAroundInterceptor($firstInterceptor)
            ->build();

        $this->assertEquals(
            8,
            $messaging->sendDirectToChannel('someName', 1)
        );
        ;
    }

    public function test_returning_array_from_service_activator()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();

        $this->assertEquals(
            ['some' => 'test'],
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
        ;
    }

    public function test_returning_array_and_changing_headers_from_service_activator()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withChangingHeaders(true)
            )
            ->build();

        $receivedMessage = $messaging->sendDirectToChannelWithMessageReply('inputChannel', 'test');

        $this->assertNotNull($receivedMessage);
        $this->assertEquals('test', $receivedMessage->getPayload());
        $this->assertArrayHasKey('some', $receivedMessage->getHeaders()->headers());
        $this->assertEquals('test', $receivedMessage->getHeaders()->get('some'));
    }
}
