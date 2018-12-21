<?php

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\RequiredReference;
use SimplyCodedSoftware\Messaging\Conversion\ArrayToJson\ArrayToJsonConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\JsonToArray\JsonToArrayConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\ObjectToSerialized\SerializingConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\SerializedToObject\DeserializingConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\StringToUuid\StringToUuidConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\UuidToString\UuidToStringConverterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\NullableMessageChannel;

/**
 * Class BasicMessagingConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class BasicMessagingConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
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
    public function getName(): string
    {
        return "basicMessagingConfiguration";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ConfigurableReferenceSearchService $configurableReferenceSearchService): void
    {
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ChannelInterceptorBuilder) {
                $configuration->registerChannelInterceptor($extensionObject);
            } else if ($extensionObject instanceof MessageHandlerBuilder) {
                $configuration->registerMessageHandler($extensionObject);
            } else if ($extensionObject instanceof MessageChannelBuilder) {
                $configuration->registerMessageChannel($extensionObject);
            } else if ($extensionObject instanceof GatewayBuilder) {
                $configuration->registerGatewayBuilder($extensionObject);
            }
        }

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
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof ChannelInterceptorBuilder
            ||
            $extensionObject instanceof MessageHandlerBuilder
            ||
            $extensionObject instanceof MessageChannelBuilder
            ||
            $extensionObject instanceof GatewayBuilder;
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
}