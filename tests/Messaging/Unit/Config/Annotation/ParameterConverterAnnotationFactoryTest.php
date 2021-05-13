<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\ServiceWithSingleArgumentDefinedByConverter;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    public function test_creating_with_class_name_as_reference_name_if_no_reference_passed()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $this->assertEquals(
            [
                HeaderBuilder::create("to", "sendTo"),
                PayloadBuilder::create("content"),
                MessageConverterBuilder::create("message"),
                ReferenceBuilder::create(
                    "object",
                    \stdClass::class
                ),
                HeaderExpressionBuilder::create("name", "token", "value", false),
                ConfigurationVariableBuilder::create("environment", "env", true, null)
            ],
            $parameterConverterAnnotationFactory->createParameterWithDefaults(
                InterfaceToCall::create(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage"),
                false
            )
        );
    }

    public function test_ommiting_default_converter_if_first_argument_has_converter()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $this->assertEquals(
            [
                ReferenceBuilder::create(
                    "data",
                    \stdClass::class
                )
            ],
            $parameterConverterAnnotationFactory->createParameterWithDefaults(
                InterfaceToCall::create(ServiceWithSingleArgumentDefinedByConverter::class, "receive"),
                false
            )
        );
    }
}