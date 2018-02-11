<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use Fixture\Handler\DumbMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\ReferenceServiceConverterAnnotation;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\ReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    public function test_creating_with_class_name_as_reference_name_if_no_reference_passed()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $parameterConverterAnnotation = new ReferenceServiceConverterAnnotation();
        $parameterConverterAnnotation->parameterName = "some";

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $messageHandler = ServiceActivatorBuilder::create($relatedClassName, "sendMessage");
        $messageHandler
            ->withMethodParameterConverters([
                ReferenceServiceParameterConverterBuilder::create(
                    $parameterConverterAnnotation->parameterName,
                    $relatedClassName,
                    $messageHandler
                )
            ]);
        $messageHandler
            ->registerRequiredReference($relatedClassName);

        $messageHandlerBuilderToCompare = ServiceActivatorBuilder::create($relatedClassName, "sendMessage");
        $parameterConverterAnnotationFactory->configureParameterConverters(
            $messageHandlerBuilderToCompare,
            $relatedClassName,
            [$parameterConverterAnnotation]
        );

        $this->assertEquals(
            $messageHandler,
            $messageHandlerBuilderToCompare
        );
    }
}