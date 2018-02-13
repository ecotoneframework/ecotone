<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
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
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
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