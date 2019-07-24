<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\Expression;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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