<?php

namespace Ecotone\EventSourcing\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\EventSourcing\AggregateStreamMapping;
use Ecotone\EventSourcing\AggregateTypeMapping;
use Ecotone\EventSourcing\Attribute\AggregateType;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\EventSourcing\Attribute\ProjectionReset;
use Ecotone\EventSourcing\Attribute\ProjectionStateGateway;
use Ecotone\EventSourcing\Attribute\Stream;
use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionChannelAdapter;
use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionEventHandler;
use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionExecutorBuilder;
use Ecotone\EventSourcing\EventMapper;
use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\EventSourcing\EventSourcingRepositoryBuilder;
use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\EventStreamEmitter;
use Ecotone\EventSourcing\ProjectionLifeCycleConfiguration;
use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\EventSourcing\ProjectionSetupConfiguration;
use Ecotone\EventSourcing\ProjectionStreamSource;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\PropagateHeaders;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Filter\MessageFilterBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\Config\ModellingHandlerModule;
use Prooph\EventStore\Projection\ReadModelProjector;
use Ramsey\Uuid\Uuid;

#[ModuleAnnotation]
class EventSourcingModule extends NoExternalConfigurationModule
{
    public const NAME = 'eventSourcing';

    public const ECOTONE_ES_STOP_PROJECTION   = 'ecotone:es:stop-projection';
    public const ECOTONE_ES_RESET_PROJECTION  = 'ecotone:es:reset-projection';
    public const ECOTONE_ES_DELETE_PROJECTION = 'ecotone:es:delete-projection';
    public const ECOTONE_ES_INITIALIZE_PROJECTION = 'ecotone:es:initialize-projection';
    /**
     * @var ProjectionSetupConfiguration[]
     */
    private array $projectionSetupConfigurations;
    /** @var ServiceActivatorBuilder[] */
    private array $projectionLifeCycleServiceActivators = [];
    private EventMapper $eventMapper;
    private AggregateStreamMapping $aggregateToStreamMapping;
    private AggregateTypeMapping $aggregateTypeMapping;
    /** @var InterfaceToCall[] */
    private array $relatedInterfaces = [];
    /** @var string[] */
    private array $requiredReferences = [];

    /**
     * @var ProjectionSetupConfiguration[]
     * @var ServiceActivatorBuilder[]
     * @var GatewayProxyBuilder[]
     */
    private function __construct(array $projectionConfigurations, array $projectionLifeCycleServiceActivators, EventMapper $eventMapper, AggregateStreamMapping $aggregateToStreamMapping, AggregateTypeMapping $aggregateTypeMapping, array $relatedInterfaces, array $requiredReferences, private array $projectionStateGateways)
    {
        $this->projectionSetupConfigurations        = $projectionConfigurations;
        $this->projectionLifeCycleServiceActivators = $projectionLifeCycleServiceActivators;
        $this->eventMapper                          = $eventMapper;
        $this->aggregateToStreamMapping             = $aggregateToStreamMapping;
        $this->aggregateTypeMapping                 = $aggregateTypeMapping;
        $this->relatedInterfaces                    = $relatedInterfaces;
        $this->requiredReferences                   = $requiredReferences;
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $fromClassToNameMapping = [];
        $fromNameToClassMapping = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(NamedEvent::class) as $namedEventClass) {
            /** @var NamedEvent $attribute */
            $attribute = $annotationRegistrationService->getAttributeForClass($namedEventClass, NamedEvent::class);

            $fromClassToNameMapping[$namedEventClass]      = $attribute->getName();
            $fromNameToClassMapping[$attribute->getName()] = $namedEventClass;
        }

        $aggregateToStreamMapping = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(Stream::class) as $aggregateWithCustomStream) {
            /** @var Stream $attribute */
            $attribute = $annotationRegistrationService->getAttributeForClass($aggregateWithCustomStream, Stream::class);

            $aggregateToStreamMapping[$aggregateWithCustomStream] = $attribute->getName();
        }

        $aggregateTypeMapping = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(AggregateType::class) as $aggregateWithCustomType) {
            /** @var Stream $attribute */
            $attribute = $annotationRegistrationService->getAttributeForClass($aggregateWithCustomType, AggregateType::class);

            $aggregateTypeMapping[$aggregateWithCustomType] = $attribute->getName();
        }

        $projectionStateGateways = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(ProjectionStateGateway::class) as $projectionStateGatewayConfiguration) {
            /** @var ProjectionStateGateway $attribute */
            $attribute = $projectionStateGatewayConfiguration->getAnnotationForMethod();

            $projectionStateGateways[] = GatewayProxyBuilder::create(
                $projectionStateGatewayConfiguration->getClassName(),
                $projectionStateGatewayConfiguration->getClassName(),
                $projectionStateGatewayConfiguration->getMethodName(),
                ProjectionManagerBuilder::getProjectionManagerActionChannel(
                    $attribute->getProjectioManagerReference(),
                    'getProjectionState'
                )
            )->withParameterConverters([
                GatewayHeaderValueBuilder::create('ecotone.eventSourcing.manager.name', $attribute->getProjectionName()),
            ]);
        }

        $projectionClassNames                  = $annotationRegistrationService->findAnnotatedClasses(Projection::class);
        $projectionEventHandlers               = $annotationRegistrationService->findCombined(Projection::class, EventHandler::class);
        $projectionSetupConfigurations              = [];
        $projectionLifeCyclesServiceActivators = [];

        $relatedInterfaces  = [];
        $requiredReferences = [];
        foreach ($projectionClassNames as $projectionClassName) {
            $referenceName        = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $projectionClassName);
            $requiredReferences[] = $referenceName;

            $projectionLifeCycle = ProjectionLifeCycleConfiguration::create();

            $classDefinition          = ClassDefinition::createUsingAnnotationParser(TypeDescriptor::create($projectionClassName), $annotationRegistrationService);
            $projectionInitialization = TypeDescriptor::create(ProjectionInitialization::class);
            $projectionDelete         = TypeDescriptor::create(ProjectionDelete::class);
            $projectionReset          = TypeDescriptor::create(ProjectionReset::class);
            foreach ($classDefinition->getPublicMethodNames() as $publicMethodName) {
                $relatedInterfaces[] = $interfaceToCallRegistry->getFor($projectionClassName, $publicMethodName);
                foreach ($annotationRegistrationService->getAnnotationsForMethod($projectionClassName, $publicMethodName) as $attribute) {
                    $attributeType = TypeDescriptor::createFromVariable($attribute);
                    if ($attributeType->equals($projectionInitialization)) {
                        $requestChannel                          = Uuid::uuid4()->toString();
                        $projectionLifeCycle                     = $projectionLifeCycle->withInitializationRequestChannel($requestChannel);
                        $projectionLifeCyclesServiceActivators[] = ServiceActivatorBuilder::create(
                            $referenceName,
                            $publicMethodName
                        )->withInputChannelName($requestChannel);
                    }
                    if ($attributeType->equals($projectionDelete)) {
                        $requestChannel                          = Uuid::uuid4()->toString();
                        $projectionLifeCycle                     = $projectionLifeCycle->withDeleteRequestChannel($requestChannel);
                        $projectionLifeCyclesServiceActivators[] = ServiceActivatorBuilder::create(
                            $referenceName,
                            $publicMethodName
                        )->withInputChannelName($requestChannel);
                    }
                    if ($attributeType->equals($projectionReset)) {
                        $requestChannel                          = Uuid::uuid4()->toString();
                        $projectionLifeCycle                     = $projectionLifeCycle->withResetRequestChannel($requestChannel);
                        $projectionLifeCyclesServiceActivators[] = ServiceActivatorBuilder::create(
                            $referenceName,
                            $publicMethodName
                        )->withInputChannelName($requestChannel);
                    }
                }
            }

            $attributes          = $annotationRegistrationService->getAnnotationsForClass($projectionClassName);
            /** @var Projection $projectionAttribute */
            $projectionAttribute = null;
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Projection) {
                    $projectionAttribute = $attribute;
                    break;
                }
            }

            Assert::keyNotExists($projectionSetupConfigurations, $projectionAttribute->getName(), "Can't define projection with name {$projectionAttribute->getName()} twice");

            if ($projectionAttribute->isFromAll()) {
                $projectionConfiguration = ProjectionSetupConfiguration::create(
                    $projectionAttribute->getName(),
                    $projectionLifeCycle,
                    $projectionAttribute->getEventStoreReferenceName(),
                    ProjectionStreamSource::forAllStreams()
                );
            } elseif ($projectionAttribute->getFromStreams()) {
                $projectionConfiguration = ProjectionSetupConfiguration::create(
                    $projectionAttribute->getName(),
                    $projectionLifeCycle,
                    $projectionAttribute->getEventStoreReferenceName(),
                    ProjectionStreamSource::fromStreams($projectionAttribute->getFromStreams())
                );
            } else {
                $projectionConfiguration = ProjectionSetupConfiguration::create(
                    $projectionAttribute->getName(),
                    $projectionLifeCycle,
                    $projectionAttribute->getEventStoreReferenceName(),
                    ProjectionStreamSource::fromCategories($projectionAttribute->getFromCategories())
                );
            }

            $projectionSetupConfigurations[$projectionAttribute->getName()] = $projectionConfiguration;
        }

        foreach ($projectionEventHandlers as $projectionEventHandler) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute     = $projectionEventHandler->getAnnotationForClass();
            /** @var EndpointAnnotation $handlerAttribute */
            $handlerAttribute     = $projectionEventHandler->getAnnotationForMethod();
            $projectionConfiguration = $projectionSetupConfigurations[$projectionAttribute->getName()];

            $eventHandlerChannelName = ModellingHandlerModule::getHandlerChannel($projectionEventHandler);
            $synchronousEventHandlerRequestChannel = AsynchronousModule::create($annotationRegistrationService, $interfaceToCallRegistry)->getSynchronousChannelFor($eventHandlerChannelName, $handlerAttribute->getEndpointId());
            $projectionSetupConfigurations[$projectionAttribute->getName()] = $projectionConfiguration->withProjectionEventHandler(
                ModellingHandlerModule::getNamedMessageChannelForEventHandler($projectionEventHandler, $interfaceToCallRegistry),
                $projectionEventHandler->getClassName(),
                $projectionEventHandler->getMethodName(),
                $synchronousEventHandlerRequestChannel,
                $eventHandlerChannelName
            );
        }

        return new self($projectionSetupConfigurations, $projectionLifeCyclesServiceActivators, EventMapper::createWith($fromClassToNameMapping, $fromNameToClassMapping), AggregateStreamMapping::createWith($aggregateToStreamMapping), AggregateTypeMapping::createWith($aggregateTypeMapping), $relatedInterfaces, array_unique($requiredReferences), $projectionStateGateways);
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $moduleReferenceSearchService->store(EventMapper::class, $this->eventMapper);
        $moduleReferenceSearchService->store(AggregateStreamMapping::class, $this->aggregateToStreamMapping);
        $moduleReferenceSearchService->store(AggregateTypeMapping::class, $this->aggregateTypeMapping);
        $configuration->registerRelatedInterfaces($this->relatedInterfaces);
        $configuration->requireReferences($this->requiredReferences);

        $projectionRunningConfigurations = [];
        $eventSourcingConfiguration = EventSourcingConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof EventSourcingConfiguration) {
                $eventSourcingConfiguration = $extensionObject;
            } elseif ($extensionObject instanceof ProjectionRunningConfiguration) {
                $projectionRunningConfigurations[$extensionObject->getProjectionName()] = $extensionObject;
            }
        }

        foreach ($this->projectionSetupConfigurations as $index => $projectionSetupConfiguration) {
            $generatedChannelName  = Uuid::uuid4()->toString();
            $projectionRunningConfiguration = ProjectionRunningConfiguration::createEventDriven($projectionSetupConfiguration->getProjectionName());
            if (array_key_exists($projectionSetupConfiguration->getProjectionName(), $projectionRunningConfigurations)) {
                $projectionRunningConfiguration = $projectionRunningConfigurations[$projectionSetupConfiguration->getProjectionName()];
            }
            $projectionSetupConfiguration = $projectionSetupConfiguration
                                    ->withOptions([
                                        ReadModelProjector::OPTION_CACHE_SIZE => $projectionRunningConfiguration->getAmountOfCachedStreamNames(),
                                        ReadModelProjector::OPTION_SLEEP => $projectionRunningConfiguration->getWaitBeforeCallingESWhenNoEventsFound(),
                                        ReadModelProjector::OPTION_PERSIST_BLOCK_SIZE => $projectionRunningConfiguration->getPersistChangesAfterAmountOfOperations(),
                                        ReadModelProjector::OPTION_LOCK_TIMEOUT_MS => $projectionRunningConfiguration->getProjectionLockTimeout(),
                                        ReadModelProjector::DEFAULT_UPDATE_LOCK_THRESHOLD => $projectionRunningConfiguration->getUpdateLockTimeoutAfter(),
                                    ]);

            $projectionExecutorBuilder = new ProjectionExecutorBuilder($eventSourcingConfiguration, $projectionSetupConfiguration, $this->projectionSetupConfigurations, $projectionRunningConfiguration, 'execute');
            $projectionExecutorBuilder = $projectionExecutorBuilder->withInputChannelName($generatedChannelName);
            $configuration->registerMessageHandler($projectionExecutorBuilder);

            foreach ($projectionSetupConfiguration->getProjectionEventHandlerConfigurations() as $projectionEventHandler) {
                $configuration->registerBeforeSendInterceptor(MethodInterceptor::create(
                    Uuid::uuid4()->toString(),
                    $interfaceToCallRegistry->getFor(ProjectionFlowController::class, 'preSend'),
                    ServiceActivatorBuilder::createWithDirectReference(new ProjectionFlowController($projectionRunningConfiguration->isPolling()), 'preSend'),
                    Precedence::SYSTEM_PRECEDENCE_BEFORE,
                    $projectionEventHandler->getClassName() . '::' . $projectionEventHandler->getMethodName()
                ));
                $configuration->registerBeforeMethodInterceptor(MethodInterceptor::create(
                    Uuid::uuid4()->toString(),
                    $interfaceToCallRegistry->getFor(ProjectionEventHandler::class, 'beforeEventHandler'),
                    new ProjectionExecutorBuilder($eventSourcingConfiguration, $projectionSetupConfiguration, $this->projectionSetupConfigurations, $projectionRunningConfiguration, 'beforeEventHandler'),
                    Precedence::SYSTEM_PRECEDENCE_BEFORE,
                    $projectionEventHandler->getClassName() . '::' . $projectionEventHandler->getMethodName()
                ));
            }

            if ($projectionRunningConfiguration->isPolling()) {
                $configuration->registerConsumer(
                    InboundChannelAdapterBuilder::createWithDirectObject(
                        $generatedChannelName,
                        new ProjectionChannelAdapter(),
                        'run'
                    )->withEndpointId($projectionSetupConfiguration->getProjectionName())
                );
            }
        }
        foreach ($this->projectionLifeCycleServiceActivators as $serviceActivator) {
            $configuration->registerMessageHandler($serviceActivator);
        }

        $this->registerEventStore($configuration, $eventSourcingConfiguration);
        $this->registerEventStreamEmitter($configuration, $eventSourcingConfiguration);
        $this->registerProjectionManager($configuration, $eventSourcingConfiguration);
    }

    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof EventSourcingConfiguration
            ||
            $extensionObject instanceof ProjectionRunningConfiguration;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        foreach ($serviceExtensions as $serviceExtension) {
            if ($serviceExtension instanceof EventSourcingRepositoryBuilder) {
                return [];
            }
        }

        $eventSourcingRepositories = [];
        foreach ($serviceExtensions as $extensionObject) {
            if ($extensionObject instanceof EventSourcingConfiguration) {
                $eventSourcingRepositories[] = EventSourcingRepositoryBuilder::create($extensionObject);
            }
        }

        return $eventSourcingRepositories ?: [EventSourcingRepositoryBuilder::create(EventSourcingConfiguration::createWithDefaults())];
    }

    private function registerEventStore(Configuration $configuration, EventSourcingConfiguration $eventSourcingConfiguration): void
    {
        $this->registerEventStoreAction(
            'create',
            [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), PayloadBuilder::create('streamEvents'), HeaderBuilder::create('streamMetadata', 'ecotone.eventSourcing.eventStore.streamMetadata')],
            [GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), GatewayPayloadBuilder::create('streamEvents'), GatewayHeaderBuilder::create('streamMetadata', 'ecotone.eventSourcing.eventStore.streamMetadata')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerEventStoreAction(
            'appendTo',
            [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), PayloadBuilder::create('streamEvents')],
            [GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), GatewayPayloadBuilder::create('streamEvents')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerEventStoreAction(
            'delete',
            [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName')],
            [GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerEventStoreAction(
            'hasStream',
            [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName')],
            [GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerEventStoreAction(
            'load',
            [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), HeaderBuilder::create('fromNumber', 'ecotone.eventSourcing.eventStore.fromNumber'), HeaderBuilder::createOptional('count', 'ecotone.eventSourcing.eventStore.count'), HeaderBuilder::createOptional('metadataMatcher', 'ecotone.eventSourcing.eventStore.metadataMatcher'), HeaderBuilder::create('deserialize', 'ecotone.eventSourcing.eventStore.deserialize')],
            [GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), GatewayHeaderBuilder::create('fromNumber', 'ecotone.eventSourcing.eventStore.fromNumber'), GatewayHeaderBuilder::create('count', 'ecotone.eventSourcing.eventStore.count'), GatewayHeaderBuilder::create('metadataMatcher', 'ecotone.eventSourcing.eventStore.metadataMatcher'), GatewayHeaderBuilder::create('deserialize', 'ecotone.eventSourcing.eventStore.deserialize')],
            $eventSourcingConfiguration,
            $configuration
        );

        foreach ($this->projectionStateGateways as $projectionStateGateway) {
            $configuration->registerGatewayBuilder($projectionStateGateway);
        }
    }

    private function registerProjectionManager(Configuration $configuration, EventSourcingConfiguration $eventSourcingConfiguration): void
    {
        $this->registerProjectionManagerAction(
            'deleteProjection',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name'), HeaderBuilder::create('deleteEmittedEvents', 'ecotone.eventSourcing.manager.deleteEmittedEvents')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration,
            self::ECOTONE_ES_DELETE_PROJECTION,
            [ConsoleCommandParameter::create('name', 'ecotone.eventSourcing.manager.name', false), ConsoleCommandParameter::createWithDefaultValue('deleteEmittedEvents', 'ecotone.eventSourcing.manager.deleteEmittedEvents', true, true)]
        );

        $this->registerProjectionManagerAction(
            'resetProjection',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration,
            self::ECOTONE_ES_RESET_PROJECTION,
            [ConsoleCommandParameter::create('name', 'ecotone.eventSourcing.manager.name', false)]
        );

        $this->registerProjectionManagerAction(
            'stopProjection',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration,
            self::ECOTONE_ES_STOP_PROJECTION,
            [ConsoleCommandParameter::create('name', 'ecotone.eventSourcing.manager.name', false)]
        );

        $this->registerProjectionManagerAction(
            'initializeProjection',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration,
            self::ECOTONE_ES_INITIALIZE_PROJECTION,
            [ConsoleCommandParameter::create('name', 'ecotone.eventSourcing.manager.name', false)]
        );

        $this->registerProjectionManagerAction(
            'hasInitializedProjectionWithName',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerProjectionManagerAction(
            'getProjectionStatus',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration
        );

        $this->registerProjectionManagerAction(
            'getProjectionState',
            [HeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            [GatewayHeaderBuilder::create('name', 'ecotone.eventSourcing.manager.name')],
            $eventSourcingConfiguration,
            $configuration
        );
    }

    private function registerProjectionManagerAction(string $methodName, array $endpointConverters, array $gatewayConverters, EventSourcingConfiguration $eventSourcingConfiguration, Configuration $configuration, ?string $consoleCommandName = null, array $consoleCommandParameters = []): void
    {
        $messageHandlerBuilder = ProjectionManagerBuilder::create($methodName, $endpointConverters, $eventSourcingConfiguration, $this->projectionSetupConfigurations);
        $configuration->registerMessageHandler($messageHandlerBuilder);
        $configuration->registerGatewayBuilder(
            GatewayProxyBuilder::create($eventSourcingConfiguration->getProjectManagerReferenceName(), ProjectionManager::class, $methodName, $messageHandlerBuilder->getInputMessageChannelName())
                ->withParameterConverters($gatewayConverters)
        );

        if ($consoleCommandName) {
            $configuration->registerConsoleCommand(
                ConsoleCommandConfiguration::create(
                    $messageHandlerBuilder->getInputMessageChannelName(),
                    $consoleCommandName,
                    $consoleCommandParameters
                )
            );
        }
    }

    private function registerEventStoreAction(string $methodName, array $endpointConverters, array $gatewayConverters, EventSourcingConfiguration $eventSourcingConfiguration, Configuration $configuration): void
    {
        $messageHandlerBuilder = EventStoreBuilder::create($methodName, $endpointConverters, $eventSourcingConfiguration);
        $configuration->registerMessageHandler($messageHandlerBuilder);

        $configuration->registerGatewayBuilder(
            GatewayProxyBuilder::create($eventSourcingConfiguration->getEventStoreReferenceName(), EventStore::class, $methodName, $messageHandlerBuilder->getInputMessageChannelName())
                ->withParameterConverters($gatewayConverters)
        );
    }

    private function registerEventStreamEmitter(Configuration $configuration, EventSourcingConfiguration $eventSourcingConfiguration): void
    {
        $eventSourcingConfiguration = (clone $eventSourcingConfiguration)->withSimpleStreamPersistenceStrategy();

        $eventStoreHandler = EventStoreBuilder::create('appendTo', [HeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), PayloadBuilder::create('streamEvents')], $eventSourcingConfiguration)
                                        ->withInputChannelName(Uuid::uuid4()->toString());
        $configuration->registerMessageHandler($eventStoreHandler);

        $eventBusChannelName = Uuid::uuid4()->toString();
        $configuration->registerMessageHandler(
            SplitterBuilder::createMessagePayloadSplitter()
                ->withInputChannelName($eventBusChannelName)
                ->withOutputMessageChannel(BusModule::EVENT_CHANNEL_NAME_BY_OBJECT)
        );

        $routerHandler =
            ChainMessageHandlerBuilder::create()
                ->withInputChannelName(Uuid::uuid4()->toString())
                ->chain(MessageFilterBuilder::createBoolHeaderFilter(ProjectionEventHandler::PROJECTION_IS_REBUILDING))
                ->withOutputMessageHandler(RouterBuilder::createRecipientListRouter([
                    $eventStoreHandler->getInputMessageChannelName(),
                    $eventBusChannelName,
                ]));
        $configuration->registerMessageHandler($routerHandler);

        $configuration->registerGatewayBuilder(
            GatewayProxyBuilder::create(EventStreamEmitter::class, EventStreamEmitter::class, 'linkTo', $routerHandler->getInputMessageChannelName())
                ->withEndpointAnnotations([new PropagateHeaders()])
                ->withParameterConverters([GatewayHeaderBuilder::create('streamName', 'ecotone.eventSourcing.eventStore.streamName'), GatewayPayloadBuilder::create('streamEvents')], )
        );

        $streamNameMapperChannel = Uuid::uuid4()->toString();
        $mapProjectionNameToStreamName = TransformerBuilder::createWithDirectObject(new StreamNameMapper(), 'map')
            ->withInputChannelName($streamNameMapperChannel)
            ->withOutputMessageChannel($routerHandler->getInputMessageChannelName());
        $configuration->registerMessageHandler($mapProjectionNameToStreamName);

        $configuration->registerGatewayBuilder(
            GatewayProxyBuilder::create(EventStreamEmitter::class, EventStreamEmitter::class, 'emit', $streamNameMapperChannel)
                ->withEndpointAnnotations([new PropagateHeaders()])
                ->withParameterConverters([GatewayPayloadBuilder::create('streamEvents')])
        );
    }

    public function getModulePackageName(): string
    {
        return self::NAME;
    }
}
