<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessageHandlerRegisterConfiguration implements AnnotationConfiguration
{
    /**
     * @var ConfigurationVariableRetrievingService
     */
    private $configurationVariableRetrievingService;
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    private function __construct(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->configurationVariableRetrievingService = $configurationVariableRetrievingService;
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new static($configurationVariableRetrievingService, $classLocator, $classMetadataReader);
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);
        $parameterConvertAnnotationFactory = ParameterConverterAnnotationFactory::create();


        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpointAnnotation::class,$this->getMessageHandlerAnnotation()) as $annotationRegistration) {
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
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}