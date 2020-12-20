<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Reference;
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
        $referenceAnnotation = new Reference("object");

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $methodName = "sendMessage";

        $this->assertEquals(
            [
                HeaderBuilder::create("to", "sendTo"),
                PayloadBuilder::create("content"),
                MessageConverterBuilder::create("message"),
                ReferenceBuilder::create(
                    $referenceAnnotation->getReferenceName(),
                    \stdClass::class
                ),
                HeaderExpressionBuilder::create("name", "token", "value", false),
                ConfigurationVariableBuilder::create("environment", "env", true, null)
            ],
            $parameterConverterAnnotationFactory->createParameterWithDefaults(
                InterfaceToCall::create($relatedClassName, $methodName),
                false
            )
        );
    }
}