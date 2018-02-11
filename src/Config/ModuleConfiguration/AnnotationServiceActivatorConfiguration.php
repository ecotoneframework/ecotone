<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\HeaderParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\MessageParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\PayloadParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\ReferenceServiceConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\HeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\MessageParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\ReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationServiceActivatorConfiguration implements AnnotationConfiguration
{
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * @inheritDoc
     */
    public function setClassLocator(ClassLocator $classLocator): void
    {
        $this->classLocator = $classLocator;
    }

    /**
     * @inheritDoc
     */
    public function setClassMetadataReader(ClassMetadataReader $classMetadataReader): void
    {
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
    {
        /** @var MessageEndpoint[] $messageEndpoints */
        $messageEndpoints = $this->classLocator->getAllClassesWithAnnotation(MessageEndpoint::class);
        $parameterConvertAnnotationFactory = ParameterConverterAnnotationFactory::create();

        foreach ($messageEndpoints as $messageEndpointClass) {
            /** @var MessageEndpoint $messageEndpointAnnotation */
            $messageEndpointAnnotation = $this->classMetadataReader->getAnnotationForClass($messageEndpointClass, MessageEndpoint::class);
            $methods = $this->classMetadataReader->getMethodsWithAnnotation($messageEndpointClass, ServiceActivatorAnnotation::class);

            foreach ($methods as $method) {
                /** @var ServiceActivatorAnnotation $serviceActivatorAnnotation */
                $serviceActivatorAnnotation = $this->classMetadataReader->getAnnotationForMethod($messageEndpointClass, $method, ServiceActivatorAnnotation::class);

                $serviceActivatorBuilder = ServiceActivatorBuilder::create($messageEndpointAnnotation->referenceName, $method);
                $parameterConvertAnnotationFactory->configureParameterConverters($serviceActivatorBuilder, $messageEndpointClass, $serviceActivatorAnnotation->parameterConverters);

                $configuration->registerMessageHandler(
                    $serviceActivatorBuilder
                        ->withRequiredReply($serviceActivatorAnnotation->requiresReply)
                        ->withInputMessageChannel($serviceActivatorAnnotation->inputChannel)
                        ->withOutputChannel($serviceActivatorAnnotation->outputChannel)
                        ->withConsumerName($messageEndpointAnnotation->referenceName)
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        // TODO: Implement postConfigure() method.
    }
}