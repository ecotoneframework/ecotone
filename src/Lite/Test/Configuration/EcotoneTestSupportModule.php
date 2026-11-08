<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Console\ConsoleWriter;
use Ecotone\Api\Console\InMemoryConsoleWriter;
use Ecotone\Api\EventSourcing\EventSourcingConfiguration;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Api\Lite\Test\MessagingTestSupport;
use Ecotone\EventSourcing\EventStore\AppendStrategy\AppendStrategy;
use Ecotone\EventSourcing\EventStore\AsGivenRecordedEventFormat;
use Ecotone\EventSourcing\EventStore\GuardedTagBump;
use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\EventSourcing\EventStore\RecordedEventFormat;
use Ecotone\EventSourcing\EventStore\Tag\InMemoryTagCollaborator;
use Ecotone\EventSourcing\Tagging\Config\DynamicConsistencyBoundary;
use Ecotone\Lite\Test\DatabaseTablesSetUp;
use Ecotone\Lite\Test\DatabaseTablesSetUpReference;
use Ecotone\Lite\Test\WithoutDatabaseTables;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Console\DelegatingConsoleWriter;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Projecting\Config\StreamFilterRegistryModule;
use Ecotone\Projecting\InMemory\InMemoryEventStoreStreamSource;
use Ecotone\Projecting\InMemory\InMemoryProjectionStateStorage;
use Ecotone\Projecting\InMemory\InMemoryTagPartitionProvider;
use Ecotone\Projecting\PartitionProviderReference;
use Ecotone\Projecting\ProjectionOperations;
use Ecotone\Projecting\ProjectionStateStorageReference;
use Ecotone\Projecting\StreamSourceReference;
use Ecotone\Test\StaticPsrClock;
use Psr\Clock\ClockInterface;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class EcotoneTestSupportModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const RECORD_COMMAND = 'recordCommand';
    public const RECORD_EVENT = 'recordEvent';
    public const RECORD_QUERY = 'recordQuery';
    public const POP_RECORDED_EVENT_MESSAGES = 'popRecordedEventMessages';
    public const POP_RECORDED_EVENTS = 'popRecordedEvents';
    public const POP_RECORDED_COMMANDS = 'popRecordedCommands';
    public const POP_RECORDED_EVENT_MESSAGES_OF_TYPE = 'popRecordedEventMessagesOfType';
    public const POP_RECORDED_COMMAND_MESSAGES_OF_TYPE = 'popRecordedCommandMessagesOfType';
    public const POP_RECORDED_COMMAND_MESSAGES = 'popRecordedCommandMessages';
    public const POP_RECORDED_QUERIES = 'popRecordedQueries';
    public const POP_RECORDED_QUERY_MESSAGES = 'popRecordedQueryMessages';
    public const DISCARD_MESSAGES = 'discardRecordedMessages';
    public const RELEASE_DELAYED_MESSAGES = 'releaseMessagesAwaitingFor';
    public const POP_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS = 'popRecordedMessagePayloadsFrom';
    public const POP_SPIED_CHANNEL_RECORDED_MESSAGES = 'popRecordedMessagesFrom';

    /**
     * @param string[] $tagPartitionedProjectionNames
     */
    private function __construct(private array $spiedChannels, private array $tagPartitionedProjectionNames)
    {

    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $spiedChannels = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(CommandHandler::class) as $messageHandler) {
            /** @var CommandHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(EventHandler::class) as $messageHandler) {
            /** @var EventHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(InternalHandler::class) as $messageHandler) {
            /** @var InternalHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }

        return new self($spiedChannels, StreamFilterRegistryModule::tagPartitionedProjectionNames($annotationRegistrationService));
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $testConfiguration = ExtensionObjectResolver::resolveUnique(TestConfiguration::class, $extensionObjects, TestConfiguration::createWithDefaults());
        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $extensionObjects, BootstrapConfiguration::createWithDefaults());

        // In memory consumer position tracker
        $messagingConfiguration->registerServiceDefinition(
            InMemoryConsumerPositionTracker::class,
            new Definition(InMemoryConsumerPositionTracker::class)
        );
        // Register in-memory consumer position tracker as default one
        if ($testConfiguration->inMemoryConsumerPositionTracker) {
            $messagingConfiguration->registerServiceDefinition(
                ConsumerPositionTracker::class,
                new Reference(InMemoryConsumerPositionTracker::class)
            );
        }

        $this->registerInMemoryEventStoreIfNeeded($messagingConfiguration, $extensionObjects, $bootstrapConfiguration);

        $databaseTablesSetUp = ExtensionObjectResolver::resolve(DatabaseTablesSetUpReference::class, $extensionObjects)[0] ?? null;
        $messagingConfiguration->registerServiceDefinition(
            DatabaseTablesSetUp::class,
            $databaseTablesSetUp === null
                ? new Definition(WithoutDatabaseTables::class, [new Reference(ProjectionOperations::class)])
                : new Reference($databaseTablesSetUp->referenceName)
        );

        $messagingConfiguration->registerServiceDefinition(
            InMemoryConsoleWriter::class,
            new Definition(InMemoryConsoleWriter::class)
        );
        $messagingConfiguration->registerServiceDefinition(
            ConsoleWriter::class,
            new Definition(DelegatingConsoleWriter::class, [
                new Reference(InMemoryConsoleWriter::class),
            ])
        );

        $messagingConfiguration->registerServiceDefinition(EcotoneClockInterface::class, new Definition(
            Clock::class,
            [
                new Reference(ClockInterface::class, ContainerImplementation::NULL_ON_INVALID_REFERENCE),
                new Definition(StaticPsrClock::class, [$testConfiguration->timeAt?->format('Y-m-d H:i:s.u P') ?? 'now']),
            ],
            factory: [Clock::class, 'createWithFallback']
        ));
        $messagingConfiguration->registerServiceDefinition(PinnedTestClockInterceptor::class, new Definition(PinnedTestClockInterceptor::class, [new Reference(EcotoneClockInterface::class)]));
        $messagingConfiguration->registerAroundMethodInterceptor(AroundInterceptorBuilder::create(
            PinnedTestClockInterceptor::class,
            $interfaceToCallRegistry->getFor(PinnedTestClockInterceptor::class, 'handleAtPinnedTime'),
            Precedence::TRACING_PRECEDENCE - 1,
            AsynchronousRunningEndpoint::class,
        ));

        $messagingConfiguration->registerServiceDefinition(MessageCollectorHandler::class, new Definition(MessageCollectorHandler::class));
        $this->registerMessageCollector($messagingConfiguration, $interfaceToCallRegistry);
        $this->registerMessageReleasingHandler($messagingConfiguration);

        $messagingConfiguration->registerServiceDefinition(AllowMissingDestination::class, new Definition(AllowMissingDestination::class));
        $allowMissingDestinationInterfaceToCall = $interfaceToCallRegistry->getFor(AllowMissingDestination::class, 'invoke');
        if (! $testConfiguration->failOnNotFoundHandler) {
            foreach ([CommandBus::class, QueryBus::class] as $busWithOptionalDestination) {
                $messagingConfiguration
                    ->registerAroundMethodInterceptor(AroundInterceptorBuilder::create(
                        AllowMissingDestination::class,
                        $allowMissingDestinationInterfaceToCall,
                        Precedence::DEFAULT_PRECEDENCE,
                        $busWithOptionalDestination
                    ));
            }
        }

        if ($testConfiguration->pollableChannelMediaTypeConversion) {
            $messagingConfiguration
                ->registerChannelInterceptor(new SerializationChannelAdapterBuilder($testConfiguration->channelToConvertOn, $testConfiguration->pollableChannelMediaTypeConversion));
        }

        foreach ($this->spiedChannels as $spiedChannel) {
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($spiedChannel));
        }
        $spiedChannels = array_unique(array_merge($testConfiguration->spiedChannelNames, $this->spiedChannels));
        foreach ($spiedChannels as $spiedChannel) {
            $messagingConfiguration
                ->registerChannelInterceptor(new SpiedChannelAdapterBuilder($spiedChannel));
        }

    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $serviceExtensions, BootstrapConfiguration::createWithDefaults());
        // Check if InMemoryEventStore will be registered
        $shouldRegisterInMemoryStreamSource = false;

        if (! $bootstrapConfiguration->isModulePackageEnabled(ModulePackageList::EVENT_SOURCING_PACKAGE)) {
            // EVENT_SOURCING_PACKAGE is disabled, so we'll use InMemoryEventStore
            $shouldRegisterInMemoryStreamSource = true;
        } else {
            // EVENT_SOURCING_PACKAGE is enabled, check if it's in-memory mode
            $hasEventSourcingConfiguration = false;
            foreach ($serviceExtensions as $extensionObject) {
                if (class_exists(EventSourcingConfiguration::class) && $extensionObject instanceof EventSourcingConfiguration) {
                    $hasEventSourcingConfiguration = true;
                    if ($extensionObject->isInMemory()) {
                        $shouldRegisterInMemoryStreamSource = true;
                    }
                    break;
                }
            }

            if (! $hasEventSourcingConfiguration) {
                $shouldRegisterInMemoryStreamSource = false;
            }
        }

        if (! $shouldRegisterInMemoryStreamSource) {
            return [];
        }

        return [
            new StreamSourceReference(InMemoryEventStoreStreamSource::class),
            new ProjectionStateStorageReference(InMemoryProjectionStateStorage::class),
            ...($this->tagPartitionedProjectionNames === [] ? [] : [new PartitionProviderReference(InMemoryTagPartitionProvider::class, $this->tagPartitionedProjectionNames)]),
        ];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::TEST_PACKAGE;
    }

    private static function inputChannelName(string $methodName): string
    {
        return 'test_support.message_collector.' .$methodName;
    }

    private function registerMessageReleasingHandler(Configuration $configuration): void
    {
        $configuration->registerServiceDefinition(DelayedMessageReleaseHandler::class, new Definition(DelayedMessageReleaseHandler::class));
        $configuration
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                DelayedMessageReleaseHandler::class,
                self::RELEASE_DELAYED_MESSAGES
            )
                ->withMethodParameterConverters([
                    HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                    PayloadBuilder::create('timeInMillisecondsOrDateTime'),
                    ReferenceBuilder::create('channelResolver', ChannelResolver::class),
                ])
                ->withInputChannelName(self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::RELEASE_DELAYED_MESSAGES,
                self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)
            )->withParameterConverters([
                GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                GatewayPayloadBuilder::create('timeInMillisecondsOrDateTime'),
            ]));
    }

    private function registerMessageCollector(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::RECORD_EVENT
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_EVENT)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::RECORD_COMMAND
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_COMMAND)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::RECORD_QUERY
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_QUERY)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_EVENTS
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_EVENTS)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_EVENT_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_EVENT_MESSAGES)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_EVENT_MESSAGES_OF_TYPE
            )
                ->withMethodParameterConverters([PayloadBuilder::create('className')])
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_EVENT_MESSAGES_OF_TYPE)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_COMMAND_MESSAGES_OF_TYPE
            )
                ->withMethodParameterConverters([PayloadBuilder::create('className')])
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_COMMAND_MESSAGES_OF_TYPE)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_COMMANDS
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_COMMANDS)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_COMMAND_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_COMMAND_MESSAGES)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_QUERIES
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_QUERIES)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_RECORDED_QUERY_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::POP_RECORDED_QUERY_MESSAGES)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::DISCARD_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::DISCARD_MESSAGES)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS
            )
                ->withMethodParameterConverters([
                    HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                ])
                ->withInputChannelName(self::inputChannelName(self::POP_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS)))
            ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(
                MessageCollectorHandler::class,
                self::POP_SPIED_CHANNEL_RECORDED_MESSAGES
            )
                ->withMethodParameterConverters([
                    HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                ])
                ->withInputChannelName(self::inputChannelName(self::POP_SPIED_CHANNEL_RECORDED_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_EVENTS,
                self::inputChannelName(self::POP_RECORDED_EVENTS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_EVENT_MESSAGES,
                self::inputChannelName(self::POP_RECORDED_EVENT_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_EVENT_MESSAGES_OF_TYPE,
                self::inputChannelName(self::POP_RECORDED_EVENT_MESSAGES_OF_TYPE)
            )->withParameterConverters([GatewayPayloadBuilder::create('className')]))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_COMMAND_MESSAGES_OF_TYPE,
                self::inputChannelName(self::POP_RECORDED_COMMAND_MESSAGES_OF_TYPE)
            )->withParameterConverters([GatewayPayloadBuilder::create('className')]))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_COMMANDS,
                self::inputChannelName(self::POP_RECORDED_COMMANDS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_COMMAND_MESSAGES,
                self::inputChannelName(self::POP_RECORDED_COMMAND_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_QUERIES,
                self::inputChannelName(self::POP_RECORDED_QUERIES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_RECORDED_QUERY_MESSAGES,
                self::inputChannelName(self::POP_RECORDED_QUERY_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::DISCARD_MESSAGES,
                self::inputChannelName(self::DISCARD_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS,
                self::inputChannelName(self::POP_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS)
            )->withParameterConverters([
                GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
            ]))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::POP_SPIED_CHANNEL_RECORDED_MESSAGES,
                self::inputChannelName(self::POP_SPIED_CHANNEL_RECORDED_MESSAGES)
            )->withParameterConverters([
                GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
            ]))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_EVENT),
                Precedence::DEFAULT_PRECEDENCE,
                EventBus::class,
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_COMMAND),
                Precedence::DEFAULT_PRECEDENCE,
                CommandBus::class
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_QUERY),
                Precedence::DEFAULT_PRECEDENCE,
                QueryBus::class
            ));
    }

    private function registerInMemoryEventStoreIfNeeded(Configuration $messagingConfiguration, array $extensionObjects, BootstrapConfiguration $bootstrapConfiguration): void
    {
        $registerInMemoryEventStoreStreamSource = false;
        if (! $bootstrapConfiguration->isModulePackageEnabled(ModulePackageList::EVENT_SOURCING_PACKAGE)) {
            DynamicConsistencyBoundary::resolveFrom($extensionObjects)->registerServicesForInMemoryStore($messagingConfiguration);

            $messagingConfiguration->registerServiceDefinition(
                InMemoryEventStore::class,
                new Definition(InMemoryEventStore::class, [Reference::to(AppendStrategy::class), Reference::to(InMemoryTagCollaborator::class), new Definition(AsGivenRecordedEventFormat::class, [Reference::to(ConversionService::REFERENCE_NAME)]), Reference::to(EcotoneClockInterface::class)]),
            );
            $messagingConfiguration->registerServiceDefinition(
                EventStore::class,
                new Reference(InMemoryEventStore::class),
            );
            $messagingConfiguration->registerServiceDefinition(
                EventStore::RAW_REFERENCE,
                new Reference(InMemoryEventStore::class),
            );
            $messagingConfiguration->registerServiceDefinition(
                GuardedTagBump::class,
                new Reference(InMemoryEventStore::class),
            );
            $registerInMemoryEventStoreStreamSource = true;
        } else {
            foreach ($extensionObjects as $extensionObject) {
                if (class_exists(EventSourcingConfiguration::class) && $extensionObject instanceof EventSourcingConfiguration) {
                    if ($extensionObject->isInMemory()) {
                        $messagingConfiguration->registerServiceDefinition(
                            InMemoryEventStore::class,
                            new Definition(InMemoryEventStore::class, [Reference::to(AppendStrategy::class), Reference::to(InMemoryTagCollaborator::class), Reference::to(RecordedEventFormat::class), Reference::to(EcotoneClockInterface::class)]),
                        );
                        $messagingConfiguration->registerServiceDefinition(
                            GuardedTagBump::class,
                            new Reference(InMemoryEventStore::class),
                        );
                        $registerInMemoryEventStoreStreamSource = true;
                    }
                    break;
                }
            }
        }

        if ($registerInMemoryEventStoreStreamSource) {
            $messagingConfiguration->registerServiceDefinition(
                InMemoryEventStoreStreamSource::class,
                new Definition(InMemoryEventStoreStreamSource::class, [
                    new Reference(InMemoryEventStore::class),
                    null,
                    null,
                    null,
                    [],
                    $this->tagPartitionedProjectionNames,
                ])
            );
            $messagingConfiguration->registerServiceDefinition(
                InMemoryTagPartitionProvider::class,
                new Definition(InMemoryTagPartitionProvider::class, [
                    new Reference(InMemoryEventStore::class),
                    $this->tagPartitionedProjectionNames,
                ])
            );
        }
    }
}
