<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ArrayToJson\ArrayToJsonConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\DeserializingConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\JsonToArray\JsonToArrayConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\SerializingConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\StringToUuidConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\UuidToStringConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\NullableMessageChannel;

/**
 * Class BasicMessagingConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class BasicMessagingConfiguration extends NoExternalConfigurationModule implements AnnotationModule
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
    public function prepare(Configuration $configuration, array $moduleExtensions): void
    {
        $configuration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        $configuration->registerConsumerFactory(new PollingConsumerBuilder());
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
        $configuration->registerConverter(new UuidToStringConverterBuilder());
        $configuration->registerConverter(new StringToUuidConverterBuilder());
        $configuration->registerConverter(new SerializingConverterBuilder());
        $configuration->registerConverter(new DeserializingConverterBuilder());
        $configuration->registerConverter(new ArrayToJsonConverterBuilder());
        $configuration->registerConverter(new JsonToArrayConverterBuilder());
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
}