<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Ecotone\Messaging\Annotation\Parameter\Expression;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_creating_with_class_name_as_reference_name_if_no_reference_passed()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $referenceAnnotation = new Reference();
        $referenceAnnotation->parameterName = "object";
        $allHeadersAnnotation = new Headers();
        $allHeadersAnnotation->parameterName = "some";

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $methodName = "sendMessage";

        $this->assertEquals(
            [
                ReferenceBuilder::create(
                    $referenceAnnotation->parameterName,
                    \stdClass::class
                ),
                AllHeadersBuilder::createWith("some")
            ],
            $parameterConverterAnnotationFactory->createParameterConverters(
                InterfaceToCall::create($relatedClassName, $methodName),
                [$referenceAnnotation, $allHeadersAnnotation]
            )
        );
    }
}