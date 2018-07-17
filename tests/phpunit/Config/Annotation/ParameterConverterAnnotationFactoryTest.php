<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Expression;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_creating_with_class_name_as_reference_name_if_no_reference_passed()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $parameterConverterAnnotation = new Reference();
        $parameterConverterAnnotation->parameterName = "object";

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $methodName = "sendMessage";
        $messageHandler = ServiceActivatorBuilder::create("some", $relatedClassName, $methodName);
        $messageHandler
            ->withMethodParameterConverters([
                ReferenceBuilder::create(
                    $parameterConverterAnnotation->parameterName,
                    \stdClass::class
                )
            ]);
        $messageHandler
            ->registerRequiredReference(\stdClass::class);

        $messageHandlerBuilderToCompare = ServiceActivatorBuilder::create("some", $relatedClassName, $methodName);
        $parameterConverterAnnotationFactory->configureParameterConverters(
            $messageHandlerBuilderToCompare,
            $relatedClassName,
            $methodName,
            [$parameterConverterAnnotation]
        );

        $this->assertEquals(
            $messageHandler,
            $messageHandlerBuilderToCompare
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_converting_for_message_to_expression_evaluation()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $parameterConverterAnnotation = new Expression();
        $parameterConverterAnnotation->parameterName = "object";
        $parameterConverterAnnotation->expression = "payload.name";

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $methodName = "sendMessage";
        $messageHandler = ServiceActivatorBuilder::create("some", $relatedClassName, $methodName);
        $messageHandler
            ->withMethodParameterConverters([
                ExpressionBuilder::create(
                    $parameterConverterAnnotation->parameterName,
                    "payload.name"
                )
            ]);

        $messageHandlerBuilderToCompare = ServiceActivatorBuilder::create("some", $relatedClassName, $methodName);

        $parameterConverterAnnotationFactory->configureParameterConverters(
            $messageHandlerBuilderToCompare,
            $relatedClassName,
            $methodName,
            [$parameterConverterAnnotation]
        );

        $this->assertEquals(
            $messageHandler,
            $messageHandlerBuilderToCompare
        );
    }
}