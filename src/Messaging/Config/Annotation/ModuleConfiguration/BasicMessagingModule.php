<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands\MessagingCommandsModule;
use Ecotone\Messaging\Config\BeforeSend\BeforeSendGateway;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
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
use Ecotone\Messaging\Endpoint\Interceptor\FinishWhenNoMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitExecutionAmountInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\SignalInterceptor;
use Ecotone\Messaging\Endpoint\Interceptor\TimeLimitInterceptor;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\Chain\ChainForwardPublisher;
use Ecotone\Messaging\Handler\Enricher\EnrichGateway;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;

#[ModuleAnnotation]
class BasicMessagingModule extends NoExternalConfigurationModule implements AnnotationModule
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
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ChannelInterceptorBuilder) {
                $messagingConfiguration->registerChannelInterceptor($extensionObject);
            } elseif ($extensionObject instanceof MessageHandlerBuilder) {
                $messagingConfiguration->registerMessageHandler($extensionObject);
            } elseif ($extensionObject instanceof MessageChannelBuilder) {
                $messagingConfiguration->registerMessageChannel($extensionObject);
            } elseif ($extensionObject instanceof GatewayProxyBuilder) {
                $messagingConfiguration->registerGatewayBuilder($extensionObject);
            } elseif ($extensionObject instanceof ChannelAdapterConsumerBuilder) {
                $messagingConfiguration->registerConsumer($extensionObject);
            } elseif ($extensionObject instanceof PollingMetadata) {
                $messagingConfiguration->registerPollingMetadata($extensionObject);
            }
        }

        if ($messagingConfiguration->isLazyLoaded()) {
            $messagingConfiguration->registerConsumerFactory(new LazyEventDrivenConsumerBuilder());
        } else {
            $messagingConfiguration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        }
        $messagingConfiguration->registerConsumerFactory(new PollingConsumerBuilder());

        $messagingConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $messagingConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
        $messagingConfiguration->registerConverter(new UuidToStringConverterBuilder());
        $messagingConfiguration->registerConverter(new StringToUuidConverterBuilder());
        $messagingConfiguration->registerConverter(new SerializingConverterBuilder());
        $messagingConfiguration->registerConverter(new DeserializingConverterBuilder());

        $messagingConfiguration->registerRelatedInterfaces([
            $interfaceToCallRegistry->getFor(FinishWhenNoMessagesInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(LimitConsumedMessagesInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(ConnectionExceptionRetryInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(LimitExecutionAmountInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(LimitMemoryUsageInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(SignalInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(TimeLimitInterceptor::class, 'postSend'),
            $interfaceToCallRegistry->getFor(ChainForwardPublisher::class, 'forward'),
            $interfaceToCallRegistry->getFor(BeforeSendGateway::class, 'execute'),
            $interfaceToCallRegistry->getFor(AcknowledgeConfirmationInterceptor::class, 'ack'),
            $interfaceToCallRegistry->getFor(InboundGatewayEntrypoint::class, 'executeEntrypoint'),
            $interfaceToCallRegistry->getFor(InboundChannelAdapterEntrypoint::class, 'executeEntrypoint'),
            $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logException'),
        ]);
        $messagingConfiguration
            ->registerInternalGateway(TypeDescriptor::create(InboundGatewayEntrypoint::class))
            ->registerInternalGateway(TypeDescriptor::create(EnrichGateway::class));

        $messagingConfiguration
            ->registerMessageHandler(
                RouterBuilder::createHeaderRouter(MessagingEntrypoint::ENTRYPOINT)
                    ->withInputChannelName(MessagingEntrypoint::ENTRYPOINT)
            );

        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypoint::class,
                MessagingEntrypoint::class,
                'send',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypoint::class,
                MessagingEntrypoint::class,
                'sendWithHeaders',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeadersBuilder::create('headers'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypoint::class,
                MessagingEntrypoint::class,
                'sendMessage',
                MessagingEntrypoint::ENTRYPOINT
            )
        );

        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypointWithHeadersPropagation::class,
                MessagingEntrypointWithHeadersPropagation::class,
                'send',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypointWithHeadersPropagation::class,
                MessagingEntrypointWithHeadersPropagation::class,
                'sendWithHeaders',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeadersBuilder::create('headers'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypointWithHeadersPropagation::class,
                MessagingEntrypointWithHeadersPropagation::class,
                'sendMessage',
                MessagingEntrypoint::ENTRYPOINT
            )
        );

        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                ConsoleCommandRunner::class,
                ConsoleCommandRunner::class,
                'execute',
                MessagingCommandsModule::ECOTONE_EXECUTE_CONSOLE_COMMAND_EXECUTOR
            )->withParameterConverters([
                GatewayHeaderBuilder::create('commandName', MessagingCommandsModule::ECOTONE_CONSOLE_COMMAND_NAME),
                GatewayPayloadBuilder::create('parameters'),
            ])
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
            $extensionObject instanceof GatewayProxyBuilder
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
            RequiredReference::create(InterfaceToCallRegistry::REFERENCE_NAME),
        ];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
