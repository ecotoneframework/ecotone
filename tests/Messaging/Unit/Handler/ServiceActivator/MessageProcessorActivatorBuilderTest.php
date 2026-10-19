<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Ecotone\Test\StaticMessageBuilder;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\StaticallyCalledService;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * licence Apache-2.0
 * @internal
 */
class MessageProcessorActivatorBuilderTest extends MessagingTestCase
{
    public function test_building_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ServiceExpectingOneArgument::class],
            [ServiceExpectingOneArgument::class => $objectToInvoke],
            enabledModules: [],
        );

        $ecotoneLite->sendDirectToChannel('withoutReturnValue', 'some');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    public function test_handler_returns_message_with_no_reply_channel_and_making_use_of_requested_reply_channel()
    {
        $message = StaticMessageBuilder::withPayload('some')
                    ->build();

        $objectToInvoke = ServiceReturningMessage::createWith($message);
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ServiceReturningMessage::class],
            [ServiceReturningMessage::class => $objectToInvoke],
            enabledModules: [],
        );

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('get', 'someOther')
        );
    }

    public function test_activating_statically_called_service()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([StaticallyCalledService::class], enabledModules: []);

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('run', 'someOther')
        );
    }

    public function test_invoking_method_on_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod($objectToInvoke, 'withoutReturnValue')
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', 'test');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    public function test_invoking_method_on_definition()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod(new Definition(ServiceExpectingOneArgument::class, [], 'create'), 'withReturnValue')
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            'test_called',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
    }

    public function test_invoking_method_on_reference_name()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $messaging = ComponentTestBuilder::create()
            ->withReference(ServiceExpectingOneArgument::class, $objectToInvoke)
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod(ServiceExpectingOneArgument::class, 'withReturnValue')
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            'test_called',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
    }

    public function test_invoking_static_method_on_reference_name_without_resolving_the_service()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod(StaticallyCalledService::class, 'run')
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            'test',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
    }

    public function test_passing_through_on_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethodInvoker(
                    MethodInvokerBuilder::create(ServiceExpectingOneArgument::create(), new InterfaceToCallReference(ServiceExpectingOneArgument::class, 'withoutReturnValue'))
                        ->withPassTroughMessageIfVoid(true)
                )
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            'test',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
    }

    public function test_ignoring_passing_through_when_service_not_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethodInvoker(
                    MethodInvokerBuilder::create(ServiceExpectingOneArgument::create(), new InterfaceToCallReference(ServiceExpectingOneArgument::class, 'withReturnValue'))
                        ->withPassTroughMessageIfVoid(true)
                )
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            'test_called',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
    }

    public function test_parameter_converters_set_after_creation_reach_the_invoked_method()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod($objectToInvoke, 'withReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('name', 'token'),
                    ])
            )
            ->build();

        $this->assertEquals(
            'from_header_called',
            $messaging->sendDirectToChannel('inputChannel', 'test', ['token' => 'from_header'])
        );
    }

    public function test_refusing_parameter_converters_on_a_chain_without_a_method_invoker()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Parameter converters can be set only on a handler created with MessageProcessorActivatorBuilder::createForMethod() or createForMethodInvoker(). This handler was created with create() and chains no MethodInvokerBuilder, so there is no method to convert parameters for. Pass the converters to MethodInvokerBuilder::create() and chain it with chainInterceptedProcessor() instead.');

        MessageProcessorActivatorBuilder::create()
            ->withMethodParameterConverters([]);
    }
}
