<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassLocator;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassMetadataReader;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessageHandlerRegisterConfiguration implements AnnotationConfiguration
{
    /**
     * @var ClassLocator
     */
    protected $classLocator;
    /**
     * @var ClassMetadataReader
     */
    protected $classMetadataReader;

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);
        $parameterConvertAnnotationFactory = ParameterConverterAnnotationFactory::create();


        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpoint::class,$this->getMessageHandlerAnnotation()) as $annotationRegistration) {
            $annotation = $annotationRegistration->getAnnotation();
            $messageHandlerBuilder = $this->createMessageHandlerFrom($annotationRegistration);

            $parameterConvertAnnotationFactory->configureParameterConverters($messageHandlerBuilder, $annotationRegistration->getMessageEndpointClass(), $annotationRegistration->getMethodName(), $annotation->parameterConverters);

            $configuration->registerMessageHandler($messageHandlerBuilder);
        }
    }

    /**
     * @param AnnotationRegistration $annotationRegistration
     * @return MessageHandlerBuilderWithParameterConverters
     */
    public abstract function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration) : MessageHandlerBuilderWithParameterConverters;

    /**
     * @return string
     */
    public abstract function getMessageHandlerAnnotation() : string;

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
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}