<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class MessageHandlerRegisterConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var AnnotationRegistrationService
     */
    private $annotationRegistrationService;
    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     */
    private function __construct(AnnotationRegistrationService $annotationRegistrationService, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory)
    {
        $this->annotationRegistrationService = $annotationRegistrationService;
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new static($annotationRegistrationService, ParameterConverterAnnotationFactory::create());
    }


    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        foreach ($this->annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, $this->getMessageHandlerAnnotation()) as $annotationRegistration) {
            $annotation = $annotationRegistration->getAnnotationForMethod();
            $messageHandlerBuilder = $this->createMessageHandlerFrom($annotationRegistration);

            $this->parameterConverterAnnotationFactory->configureParameterConverters($messageHandlerBuilder, $annotationRegistration->getClassWithAnnotation(), $annotationRegistration->getMethodName(), $annotation->parameterConverters);

            $configuration->registerMessageHandler($messageHandlerBuilder);
        }
    }

    /**
     * @return string
     */
    public abstract function getMessageHandlerAnnotation(): string;

    /**
     * @param AnnotationRegistration $annotationRegistration
     * @return MessageHandlerBuilderWithParameterConverters
     */
    public abstract function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters;

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}