<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\HeadersConversionService;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * @internal
 */
class ConfigurationVariableBuilderTest extends TestCase
{
    public function test_retrieving_from_configuration()
    {
        $interfaceParameter    = InterfaceParameter::createNotNullable('johny', TypeDescriptor::createIntegerType());
        $configurationVariable = new BoundParameterConverter(
            ConfigurationVariableBuilder::createFrom('name', $interfaceParameter),
            InterfaceToCall::create(CallableService::class, 'wasCalled')
        );


        $this->assertEquals(
            100,
            ComponentTestBuilder::create()
                ->withReference(
                    ConfigurationVariableService::REFERENCE_NAME,
                    InMemoryConfigurationVariableService::create(['name' => 100])
                )
                ->build($configurationVariable)
                ->getArgumentFrom(MessageBuilder::withPayload('some')->build())
        );
    }

    public function test_retrieving_from_configuration_using_parameter_name()
    {
        $interfaceParameter    = InterfaceParameter::createNotNullable('name', TypeDescriptor::createIntegerType());
        $configurationVariable = new BoundParameterConverter(
            ConfigurationVariableBuilder::createFrom(null, $interfaceParameter),
            InterfaceToCall::create(CallableService::class, 'wasCalled')
        );


        $this->assertEquals(
            100,
            ComponentTestBuilder::create()
                ->withReference(
                    ConfigurationVariableService::REFERENCE_NAME,
                    InMemoryConfigurationVariableService::create(['name' => 100])
                )
                ->build($configurationVariable)
                ->getArgumentFrom(MessageBuilder::withPayload('some')->build())
        );
    }

    public function test_passing_null_when_configuration_variable_missing_but_null_is_possible()
    {
        $interfaceToCall = InterfaceToCall::create(HeadersConversionService::class, 'withNullableString');
        $interfaceParameter    = $interfaceToCall->getParameterAtIndex(0);
        $configurationVariable = new BoundParameterConverter(
            ConfigurationVariableBuilder::createFrom('some', $interfaceParameter),
            $interfaceToCall
        );


        $this->assertEquals(
            '',
            ComponentTestBuilder::create()
                ->build($configurationVariable)
                ->getArgumentFrom(MessageBuilder::withPayload('some')->build())
        );
    }

    public function test_passing_default_when_configuration_variable_missing_but_default_is_provided()
    {
        $interfaceToCall = InterfaceToCall::create(HeadersConversionService::class, 'withIntDefaultValue');
        $interfaceParameter    = $interfaceToCall->getParameterAtIndex(0);

        $configurationVariable = new BoundParameterConverter(
            ConfigurationVariableBuilder::createFrom('name', $interfaceParameter),
            $interfaceToCall
        );

        $this->assertEquals(
            100,
            ComponentTestBuilder::create()
                ->build($configurationVariable)
                ->getArgumentFrom(MessageBuilder::withPayload('some')->build())
        );
    }

    public function test_throwing_exception_if_missing_configuration_variable()
    {
        $interfaceToCall = InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withReturnValue');
        $interfaceParameter    = $interfaceToCall->getInterfaceParameters()[0];
        $configurationVariable = new BoundParameterConverter(
            ConfigurationVariableBuilder::createFrom('name', $interfaceParameter),
            $interfaceToCall,
        );

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->build($configurationVariable);
    }
}
