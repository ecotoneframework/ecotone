<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\NullableMessageChannel;

/**
 * Class BasicMessagingConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class BasicMessagingConfiguration implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "basicMessagingConfiguration";
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
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver) : void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [
            RequiredReference::create(ExpressionEvaluationService::REFERENCE, ExpressionEvaluationService::class, "Expression language evaluation service")
        ];
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        $configuration->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory());
        $configuration->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory());
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}