<?php

namespace Fixture\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariable;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;

/**
 * Class ApplicationContextExtensionExample
 * @package Fixture\Annotation\ApplicationContext
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleExtensionAnnotation()
 */
class ApplicationContextModuleExtensionExample implements ApplicationContextModuleExtension
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModuleExtension
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function canHandle($messagingComponent): bool
    {
        return $messagingComponent instanceof \stdClass;
    }

    /**
     * @inheritDoc
     */
    public function registerMessagingComponent(Configuration $configuration, $messagingComponent): void
    {
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("extension"));
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return ApplicationContextModule::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}