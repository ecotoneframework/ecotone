<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;

class ConfigurationVariableBuilderTest extends TestCase
{
    public function test_retrieving_from_configuration()
    {
        $interfaceParameter    = InterfaceParameter::createNotNullable("johny", TypeDescriptor::createIntegerType());
        $configurationVariable = ConfigurationVariableBuilder::createFrom("name", $interfaceParameter);

        $this->assertEquals(
            100,
            $configurationVariable->build(InMemoryReferenceSearchService::createWith([
                ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::create([
                    "name" => 100
                ])
            ]))->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $interfaceParameter,
                MessageBuilder::withPayload("some")->build(),
                []
            )
        );
    }

    public function test_retrieving_from_configuration_using_parameter_name()
    {
        $interfaceParameter    = InterfaceParameter::createNotNullable("name", TypeDescriptor::createIntegerType());
        $configurationVariable = ConfigurationVariableBuilder::createFrom(null, $interfaceParameter);

        $this->assertEquals(
            100,
            $configurationVariable->build(InMemoryReferenceSearchService::createWith([
                ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::create([
                    "name" => 100
                ])
            ]))->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $interfaceParameter,
                MessageBuilder::withPayload("some")->build(),
                []
            )
        );
    }

    public function test_passing_null_when_configuration_variable_missing_but_null_is_possible()
    {
        $interfaceParameter    = InterfaceParameter::createNullable("name", TypeDescriptor::createIntegerType());
        $configurationVariable = ConfigurationVariableBuilder::createFrom("name", $interfaceParameter);

        $this->assertNull(
            $configurationVariable->build(InMemoryReferenceSearchService::createWith([
                ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::createEmpty()
            ]))->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $interfaceParameter,
                MessageBuilder::withPayload("some")->build(),
                []
            )
        );
    }

    public function test_passing_default_when_configuration_variable_missing_but_default_is_provided()
    {
        $defaultValue                     = 100;
        $interfaceParameter    = InterfaceParameter::create("name", TypeDescriptor::createIntegerType(), false, true, $defaultValue, false, []);
        $configurationVariable = ConfigurationVariableBuilder::createFrom("name", $interfaceParameter);

        $this->assertEquals(
            $defaultValue,
            $configurationVariable->build(InMemoryReferenceSearchService::createWith([
                ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::createEmpty()
            ]))->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $interfaceParameter,
                MessageBuilder::withPayload("some")->build(),
                []
            )
        );
    }

    public function test_throwing_exception_if_missing_configuration_variable()
    {
        $interfaceParameter    = InterfaceParameter::createNotNullable("johny", TypeDescriptor::createIntegerType());
        $configurationVariable = ConfigurationVariableBuilder::createFrom("name", $interfaceParameter);

        $this->expectException(InvalidArgumentException::class);

        $configurationVariable->build(InMemoryReferenceSearchService::createWith([
            ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::createEmpty()
        ]));
    }
}