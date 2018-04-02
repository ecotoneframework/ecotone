<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class MessageHandlerRegisterConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;
    /**
     * @var AnnotationRegistration[]
     */
    private $annotationRegistrations;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param AnnotationRegistration[] $annotationRegistrations
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     */
    private function __construct(array $annotationRegistrations, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory)
    {
        $this->annotationRegistrations = $annotationRegistrations;
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new static(
            $annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, static::getMessageHandlerAnnotation()),
            ParameterConverterAnnotationFactory::create()
        );
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        foreach ($this->annotationRegistrations as $annotationRegistration) {
            $annotation = $annotationRegistration->getAnnotationForMethod();
            $messageHandlerBuilder = $this->createMessageHandlerFrom($annotationRegistration);

            $this->parameterConverterAnnotationFactory->configureParameterConverters($messageHandlerBuilder, $annotationRegistration->getClassWithAnnotation(), $annotationRegistration->getMethodName(), $annotation->parameterConverters);

            $configuration->registerMessageHandler($messageHandlerBuilder);
        }
    }

    /**
     * @return string
     */
    public static abstract function getMessageHandlerAnnotation(): string;

    /**
     * @param AnnotationRegistration $annotationRegistration
     * @return MessageHandlerBuilderWithParameterConverters
     */
    public abstract function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters;
}