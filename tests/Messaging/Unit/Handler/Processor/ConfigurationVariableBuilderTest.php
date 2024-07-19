<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ConfigurationVariableBuilderTest extends TestCase
{
    public function test_retrieving_from_configuration()
    {
        $messaging = ComponentTestBuilder::create(configurationVariables: ['name' => 100])
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ConfigurationVariableBuilder::createFrom(
                            'name',
                            InterfaceParameter::createNotNullable('value', TypeDescriptor::createIntegerType())
                        ),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 'test')
        );
    }

    public function test_retrieving_from_configuration_using_parameter_name()
    {
        $messaging = ComponentTestBuilder::create(configurationVariables: ['value' => 100])
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ConfigurationVariableBuilder::createFrom(
                            null,
                            InterfaceParameter::createNotNullable('value', TypeDescriptor::createIntegerType())
                        ),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 'test')
        );
    }

    public function test_passing_null_when_configuration_variable_missing_but_null_is_possible()
    {
        $messaging = ComponentTestBuilder::create(configurationVariables: [])
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ConfigurationVariableBuilder::createFrom(
                            'name',
                            InterfaceParameter::createNullable('value', TypeDescriptor::createIntegerType())
                        ),
                    ])
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel, 'test')
        );
    }

    public function test_passing_default_when_configuration_variable_missing_but_default_is_provided()
    {
        $messaging = ComponentTestBuilder::create(configurationVariables: [])
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ConfigurationVariableBuilder::create(
                            'value',
                            InterfaceParameter::createNotNullable('name', TypeDescriptor::createIntegerType()),
                            false,
                            100,
                        ),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 'test')
        );
    }

    public function test_throwing_exception_if_missing_configuration_variable()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create(configurationVariables: [])
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName('inputChannel')
                    ->withMethodParameterConverters([
                        ConfigurationVariableBuilder::create(
                            'value',
                            InterfaceParameter::createNotNullable('name', TypeDescriptor::createIntegerType()),
                            true,
                            null,
                        ),
                    ])
            )
            ->build();
    }
}
