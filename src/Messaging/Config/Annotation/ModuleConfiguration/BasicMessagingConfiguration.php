<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\RequiredReference;
use Ecotone\Messaging\Conversion\ArrayToJson\ArrayToJsonConverterBuilder;
use Ecotone\Messaging\Conversion\JsonToArray\JsonToArrayConverterBuilder;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverterBuilder;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverterBuilder;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverterBuilder;
use Ecotone\Messaging\Conversion\UuidToString\UuidToStringConverterBuilder;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\EventDriven\LazyEventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitExecutionAmountInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\SignalInterceptor;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Handler\Chain\ChainForwardPublisher;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;

/**
 * Class BasicMessagingConfiguration
 * @package Ecotone\Messaging\Config\Annotation
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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
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
            } else if ($extensionObject instanceof ChannelAdapterConsumerBuilder) {
                $configuration->registerConsumer($extensionObject);
            }
        }

        if ($configuration->isLazyLoaded()) {
            $configuration->registerConsumerFactory(new LazyEventDrivenConsumerBuilder());
        }else {
            $configuration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        }
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
        $configuration->registerConverter(new UuidToStringConverterBuilder());
        $configuration->registerConverter(new StringToUuidConverterBuilder());
        $configuration->registerConverter(new SerializingConverterBuilder());
        $configuration->registerConverter(new DeserializingConverterBuilder());

        $configuration->registerRelatedInterfaces([
            InterfaceToCall::create(LimitConsumedMessagesInterceptor::class, "postSend"),
            InterfaceToCall::create(LimitExecutionAmountInterceptor::class, "postSend"),
            InterfaceToCall::create(LimitMemoryUsageInterceptor::class, "postSend"),
            InterfaceToCall::create(SignalInterceptor::class, "postSend"),
            InterfaceToCall::create(ChainForwardPublisher::class, "forward")
        ]);
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
            $extensionObject instanceof GatewayBuilder
            ||
            $extensionObject instanceof ChannelAdapterConsumerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [
            RequiredReference::create(ExpressionEvaluationService::REFERENCE, ExpressionEvaluationService::class, "Expression language evaluation service"),
            RequiredReference::create(InterfaceToCallRegistry::REFERENCE_NAME, InterfaceToCallRegistry::class, "Registry for building interface descriptions")
        ];
    }
}