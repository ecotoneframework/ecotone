<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\OnConsumerStop;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands\MessagingCommandsModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\EnumToScalar\EnumToScalarConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use Ecotone\Messaging\Conversion\ScalarToEnum\ScalarToEnumConverter;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverter;
use Ecotone\Messaging\Conversion\UuidToString\UuidToStringConverter;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PollingMetadataConverter;
use Ecotone\Messaging\Handler\Router\HeaderRouter;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
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
        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());

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

        $messagingConfiguration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        $messagingConfiguration->registerConsumerFactory(new PollingConsumerBuilder());

        $messagingConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $messagingConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(OnConsumerStop::CONSUMER_STOP_CHANNEL_NAME));
        $messagingConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create()));
        $messagingConfiguration->registerConverter(new Definition(UuidToStringConverter::class));
        $messagingConfiguration->registerConverter(new Definition(StringToUuidConverter::class));
        $messagingConfiguration->registerConverter(new Definition(SerializingConverter::class));
        $messagingConfiguration->registerConverter(new Definition(DeserializingConverter::class));
        $messagingConfiguration->registerConverter(new Definition(EnumToScalarConverter::class));
        $messagingConfiguration->registerConverter(new Definition(ScalarToEnumConverter::class));

        $messagingConfiguration
            ->registerMessageHandler(
                RouterBuilder::create(
                    new Definition(HeaderRouter::class, [MessagingEntrypoint::ENTRYPOINT]),
                    $interfaceToCallRegistry->getFor(HeaderRouter::class, 'route')
                )
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
                GatewayHeaderBuilder::create('routingSlip', MessageHeaders::ROUTING_SLIP),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypoint::class,
                MessagingEntrypoint::class,
                'sendWithHeadersWithMessageReply',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeadersBuilder::create('headers'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
                GatewayHeaderBuilder::create('routingSlip', MessageHeaders::ROUTING_SLIP),
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
                GatewayHeaderBuilder::create('routingSlip', MessageHeaders::ROUTING_SLIP),
            ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(
                MessagingEntrypointWithHeadersPropagation::class,
                MessagingEntrypointWithHeadersPropagation::class,
                'sendWithHeadersWithMessageReply',
                MessagingEntrypoint::ENTRYPOINT
            )->withParameterConverters([
                GatewayPayloadBuilder::create('payload'),
                GatewayHeadersBuilder::create('headers'),
                GatewayHeaderBuilder::create('targetChannel', MessagingEntrypoint::ENTRYPOINT),
                GatewayHeaderBuilder::create('routingSlip', MessageHeaders::ROUTING_SLIP),
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

        $messagingConfiguration->registerServiceDefinition(
            ErrorChannelService::class,
            Definition::createFor(ErrorChannelService::class, [
                Reference::to(LoggingGateway::class),
                new Definition(OutboundMessageConverter::class, [
                    DefaultHeaderMapper::createAllHeadersMapping()->getDefinition(),
                    MediaType::parseMediaType($serviceConfiguration->getDefaultSerializationMediaType()),
                ]),
                Reference::to(ConversionService::REFERENCE_NAME),
            ])
        );

        $messagingConfiguration->registerServiceDefinition(PollingMetadataConverter::class, new Definition(PollingMetadataConverter::class));

        $messagingConfiguration->registerServiceDefinition(LicenceDecider::class, new Definition(LicenceDecider::class, [$messagingConfiguration->isRunningForEnterpriseLicence()]));
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
            $extensionObject instanceof PollingMetadata
            ||
            $extensionObject instanceof ServiceConfiguration;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
