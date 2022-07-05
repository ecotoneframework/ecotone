<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\BeforeSend\BeforeSendGateway;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\RequiredReference;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverterBuilder;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverterBuilder;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverterBuilder;
use Ecotone\Messaging\Conversion\UuidToString\UuidToStringConverterBuilder;
use Ecotone\Messaging\Endpoint\AcknowledgeConfirmationInterceptor;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\EventDriven\LazyEventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Endpoint\InboundGatewayEntrypoint;
use Ecotone\Messaging\Endpoint\Interceptor\ConnectionExceptionRetryInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitExecutionAmountInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\SignalInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\TimeLimitInterceptor;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Chain\ChainForwardPublisher;
use Ecotone\Messaging\Handler\Enricher\EnrichGateway;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;

#[ModuleAnnotation]
class BasicMessagingConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
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
            } else if ($extensionObject instanceof PollingMetadata) {
                $configuration->registerPollingMetadata($extensionObject);
            }
        }

        if ($configuration->isLazyLoaded()) {
            $configuration->registerConsumerFactory(new LazyEventDrivenConsumerBuilder());
        }else {
            $configuration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        }
        $configuration->registerConsumerFactory(new PollingConsumerBuilder());

        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
        $configuration->registerConverter(new UuidToStringConverterBuilder());
        $configuration->registerConverter(new StringToUuidConverterBuilder());
        $configuration->registerConverter(new SerializingConverterBuilder());
        $configuration->registerConverter(new DeserializingConverterBuilder());

        $configuration->registerRelatedInterfaces([
            $interfaceToCallRegistry->getFor(LimitConsumedMessagesInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(ConnectionExceptionRetryInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(LimitExecutionAmountInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(LimitMemoryUsageInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(SignalInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(TimeLimitInterceptor::class, "postSend"),
            $interfaceToCallRegistry->getFor(ChainForwardPublisher::class, "forward"),
            $interfaceToCallRegistry->getFor(BeforeSendGateway::class, "execute"),
            $interfaceToCallRegistry->getFor(AcknowledgeConfirmationInterceptor::class, "ack"),
            $interfaceToCallRegistry->getFor(InboundGatewayEntrypoint::class, "executeEntrypoint"),
            $interfaceToCallRegistry->getFor(InboundChannelAdapterEntrypoint::class, "executeEntrypoint"),
            $interfaceToCallRegistry->getFor(LoggingInterceptor::class, "logException")
        ]);
        $configuration
            ->registerInternalGateway(TypeDescriptor::create(InboundGatewayEntrypoint::class))
            ->registerInternalGateway(TypeDescriptor::create(EnrichGateway::class));

        $configuration
            ->registerMessageHandler(
                RouterBuilder::createHeaderRouter(MessagingEntrypoint::ENTRYPOINT)
                    ->withInputChannelName(MessagingEntrypoint::ENTRYPOINT)
            );
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
            $extensionObject instanceof ChannelAdapterConsumerBuilder
            ||
            $extensionObject instanceof PollingMetadata;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [
            RequiredReference::create(ExpressionEvaluationService::REFERENCE),
            RequiredReference::create(InterfaceToCallRegistry::REFERENCE_NAME)
        ];
    }
}