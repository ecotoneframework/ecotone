<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Router\RouterProcessorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerProcessorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\AggregateFlow\AggregateIdMetadataConverter;
use Ecotone\Modelling\AggregateFlow\CallAggregate\CallAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateMode;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateClassDefinition;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateTestSetupServiceBuilder;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Attribute\RelatedAggregate;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Modelling\Config\Routing\RoutingEvent;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;
use Ecotone\Modelling\EventSourcingExecutor\EnterpriseAggregateMethodInvoker;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutorBuilder;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;
use Ecotone\Modelling\EventSourcingExecutor\OpenCoreAggregateMethodInvoker;
use Ecotone\Modelling\FetchAggregate;
use Ecotone\Modelling\Repository\AggregateRepositoryBuilder;
use Ecotone\Modelling\Repository\StandardRepositoryAdapterBuilder;
use Ecotone\Modelling\RepositoryBuilder;
use Ramsey\Uuid\Uuid;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class AggregrateModule implements AnnotationModule, RoutingEventHandler
{
    /**
     * @var array<string, AnnotatedFinding> $channelsToBridge key is factoryChannel, value is actionChannel registration
     */
    private array $channelsToBridge = [];
    /**
     * @var array<string> $channelsToCancel
     */
    private array $channelsToCancel = [];

    /**
     * @param string[] $aggregateClasses
     * @param AggregateClassDefinition[] $aggregateClassDefinitions
     * @param AnnotatedFinding[] $aggregateCommandHandlers
     * @param AnnotatedFinding[] $aggregateQueryHandlers
     * @param AnnotatedFinding[] $aggregateEventHandlers
     * @param string[] $aggregateRepositoryReferenceNames
     * @param AnnotatedFinding[] $gatewayRepositoryMethods
     */
    private function __construct(
        private InterfaceToCallRegistry $interfaceToCallRegistry,
        private array $aggregateClasses,
        private array $aggregateClassDefinitions,
        private array $aggregateCommandHandlers,
        private array $aggregateEventHandlers,
        private array $aggregateRepositoryReferenceNames,
        private array $gatewayRepositoryMethods,
        private EventMapper $eventMapper,
    ) {
        $this->initChannelsToBridge();
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $aggregateRepositoryClasses = $annotationRegistrationService->findAnnotatedClasses(Repository::class);

        $aggregateRepositoryReferenceNames = [];
        foreach ($aggregateRepositoryClasses as $aggregateRepositoryClass) {
            $aggregateRepositoryReferenceNames[$aggregateRepositoryClass] = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $aggregateRepositoryClass);
        }

        $aggregateClasses = $annotationRegistrationService->findAnnotatedClasses(Aggregate::class);
        $aggregateClassDefinitions = [];
        foreach ($aggregateClasses as $aggregateClass) {
            $aggregateClassDefinitions[$aggregateClass] = AggregateDefinitionResolver::resolve($aggregateClass, $interfaceToCallRegistry);
        }

        $fromClassToNameMapping = [];
        $fromNameToClassMapping = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(NamedEvent::class) as $namedEventClass) {
            /** @var NamedEvent $attribute */
            $attribute = $annotationRegistrationService->getAttributeForClass($namedEventClass, NamedEvent::class);

            if (array_key_exists($attribute->getName(), $fromNameToClassMapping)) {
                throw ConfigurationException::create(sprintf('Named Events should have unique names. However, `%s` is used more than once.', $attribute->getName()));
            }

            $fromClassToNameMapping[$namedEventClass] = $attribute->getName();
            $fromNameToClassMapping[$attribute->getName()] = $namedEventClass;
        }

        return new self(
            $interfaceToCallRegistry,
            $aggregateClasses,
            $aggregateClassDefinitions,
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            $aggregateRepositoryReferenceNames,
            $annotationRegistrationService->findAnnotatedMethods(Repository::class),
            EventMapper::createWith($fromClassToNameMapping, $fromNameToClassMapping)
        );
    }

    private function initChannelsToBridge(): void
    {
        foreach ($this->getCombinedCommandAndEventHandlersPerAggregate() as $aggregateClassname => $registrations) {
            $staticRegistrations = [];
            $staticChannelsOfAggregate = [];
            $routerBuilder = new BusRoutingMapBuilder();
            /** @var array<string, AnnotatedFinding> $channelToRegistrations */
            $channelToRegistrations = [];
            foreach ($registrations as $registration) {
                $destinationChannel = $routerBuilder->addRoutesFromAnnotatedFinding(
                    $registration,
                    $this->interfaceToCallRegistry
                );
                if ($destinationChannel) {
                    $channelToRegistrations[$destinationChannel] = $registration;
                }
                if ($this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->isFactoryMethod()) {
                    $staticRegistrations[] = $registration;
                    $staticChannelsOfAggregate[] = $destinationChannel;
                }
            }

            $routerBuilder->optimize();

            foreach ($staticRegistrations as $staticRegistration) {
                $routedKeys = $routerBuilder->getRoutesFromAnnotatedFinding($staticRegistration, $this->interfaceToCallRegistry);

                $bridgeForThisFactoryMethod = null;
                foreach ($routedKeys as $routedKey) {
                    $channels = $routerBuilder->get($routedKey);
                    $staticChannels = \array_filter($channels, fn ($channel) => \in_array($channel, $staticChannelsOfAggregate, true));
                    $actionChannels = \array_filter($channels, fn ($channel) => ! \in_array($channel, $staticChannelsOfAggregate, true));
                    if (empty($staticChannels) || empty($actionChannels)) {
                        // All channels on this route are factory or action channels, continue normal processing
                        continue;
                    }
                    if (count($staticChannels) > 1 || count($actionChannels) > 1) {
                        throw ConfigurationException::create("Message Handlers on Aggregate and Saga can be used either for single factory method and single action method together, or for multiple actions methods in {$staticRegistration->getClassName()}");
                    }
                    // Exactly one factory and one action channel, register a bridge
                    if ($bridgeForThisFactoryMethod === null) {
                        $bridgeForThisFactoryMethod = [
                            'factoryChannel' => reset($staticChannels),
                            'actionChannel' => reset($actionChannels),
                            'routes' => [$routedKey],
                        ];
                    } else {
                        // If we already have a bridge for this factory method, add the route to it
                        $bridgeForThisFactoryMethod['routes'][] = $routedKey;
                        Assert::isTrue(
                            reset($staticChannels) === $bridgeForThisFactoryMethod['factoryChannel']
                            && reset($actionChannels) === $bridgeForThisFactoryMethod['actionChannel'],
                            "Trying to register multiple factory methods for {$staticRegistration->getClassName()} under same route {$routedKey}"
                        );
                    }
                }
                if ($bridgeForThisFactoryMethod !== null) {
                    $this->channelsToBridge[$bridgeForThisFactoryMethod['factoryChannel']] = $channelToRegistrations[$bridgeForThisFactoryMethod['actionChannel']];
                    $this->channelsToCancel[] = $bridgeForThisFactoryMethod['actionChannel'];
                }
            }
        }

    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof RepositoryBuilder
            ||
            $extensionObject instanceof AggregateRepositoryBuilder;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {

        return [new StandardRepositoryAdapterBuilder(), $this];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration->registerServiceDefinition(EventMapper::class, $this->eventMapper->compile());
        $messagingConfiguration->registerConverter(new AggregateIdMetadataConverter());
        $this->initialization($messagingConfiguration, $interfaceToCallRegistry);

        $repositories = $this->aggregateRepositoryReferenceNames;
        $aggregateRepositoryBuilders = [];
        foreach ($moduleExtensions as $aggregateRepositoryBuilder) {
            if ($aggregateRepositoryBuilder instanceof AggregateRepositoryBuilder) {
                $aggregateRepositoryBuilders[] = $aggregateRepositoryBuilder;
            }
            if ($aggregateRepositoryBuilder instanceof RepositoryBuilder) {
                $referenceId = Uuid::uuid4()->toString();
                $moduleReferenceSearchService->store($referenceId, $aggregateRepositoryBuilder);
                $repositories[$referenceId] = $referenceId;
            }
        }

        $messagingConfiguration->addCompilerPass(new AggregateRepositoriesCompilerPass(
            $repositories,
            $aggregateRepositoryBuilders
        ));

        $this->registerForDirectLoadAndSaveOfAggregate($interfaceToCallRegistry, $messagingConfiguration);
        $this->registerBusinessRepositories($interfaceToCallRegistry, $messagingConfiguration);
    }

    private function registerAggregateQueryHandler(AnnotatedFinding $registration, string $destinationChannel, Configuration $configuration): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        /** @var QueryHandler $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $relatedClassInterface    = $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters      = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);
        $aggregateClassDefinition = $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($registration->getClassName()));
        $handledPayloadType       = MessageHandlerRoutingModule::getFirstParameterClassIfAny($registration, $this->interfaceToCallRegistry);
        $handledPayloadType       = $handledPayloadType ? $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($handledPayloadType)) : null;

        $configuration->registerMessageHandler(
            MessageProcessorActivatorBuilder::create()
                ->withInputChannelName($destinationChannel)
                ->withOutputMessageChannel($annotationForMethod->getOutputChannelName())
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], $handledPayloadType, $this->interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound())
                )
                ->chainInterceptedProcessor(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false, $this->interfaceToCallRegistry)
                        ->withMethodParameterConverters($parameterConverters)
                )
                ->withRequiredInterceptorNames($annotationForMethod->getRequiredInterceptorNames())
                ->withEndpointId($annotationForMethod->getEndpointId())
        );
    }

    private function registerLoadAggregate(ClassDefinition $aggregateClassDefinition, bool $canReturnNull, Configuration $configuration, MessageProcessorActivatorBuilder $chainMessageHandlerBuilder, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        /** @TODO do not require method name in save service */
        $methodName = $aggregateClassDefinition->getPublicMethodNames() ? $aggregateClassDefinition->getPublicMethodNames()[0] : '__construct';

        $configuration->registerMessageHandler(
            $chainMessageHandlerBuilder
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], null, $interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $methodName, null, $canReturnNull ? LoadAggregateMode::createContinueOnNotFound() : LoadAggregateMode::createThrowOnNotFound())
                )
                ->chain(new Definition(FetchAggregate::class))
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    public static function getRegisterAggregateLoadRepositoryInputChannel(string $className, bool $allowNulls): string
    {
        return self::getAggregateRepositoryInputChannel($className, '.will_load', false, $allowNulls);
    }

    public static function getRegisterAggregateSaveRepositoryInputChannel(string $className, bool $forTesting = false): string
    {
        return self::getAggregateRepositoryInputChannel($className, '.will_save', true, false) . ($forTesting ? '.test_setup_state' : '');
    }

    public static function getAggregateRepositoryInputChannel(string $className, string $methodName1, bool $isSave, bool $canReturnNull): string
    {
        return $className . $methodName1 . ($isSave ? '.save' : '.load' . ($canReturnNull ? '.nullable' : ''));
    }

    private function initialization(Configuration $messagingConfiguration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if ($messagingConfiguration->isRunningForEnterpriseLicence()) {
            $messagingConfiguration->registerServiceDefinition(Reference::to(EnterpriseAggregateMethodInvoker::class), new Definition(EnterpriseAggregateMethodInvoker::class));
        } else {
            $messagingConfiguration->registerServiceDefinition(Reference::to(OpenCoreAggregateMethodInvoker::class), new Definition(OpenCoreAggregateMethodInvoker::class));
        }

        $eventSourcingExecutors = [];
        foreach ($this->aggregateClassDefinitions as $aggregateClassDefinition) {
            if ($aggregateClassDefinition->isEventSourced()) {
                $eventSourcingExecutors[$aggregateClassDefinition->getClassName()] = EventSourcingHandlerExecutorBuilder::createFor(
                    $interfaceToCallRegistry->getClassDefinitionFor(Type::object($aggregateClassDefinition->getClassName())),
                    $interfaceToCallRegistry
                );
            }
        }
        $messagingConfiguration->registerServiceDefinition(
            GroupedEventSourcingExecutor::class,
            Definition::createFor(GroupedEventSourcingExecutor::class, [
                $eventSourcingExecutors,
            ])
        );


        $messagingConfiguration->registerServiceDefinition(
            AggregateDefinitionRegistry::class,
            [
                $this->aggregateClassDefinitions,
            ],
        );

        $messagingConfiguration->registerServiceDefinition(
            AggregateResolver::class,
            Definition::createFor(AggregateResolver::class, [
                Reference::to(AggregateDefinitionRegistry::class),
                Reference::to(GroupedEventSourcingExecutor::class),
                PropertyEditorAccessor::getDefinition(),
                PropertyReaderAccessor::getDefinition(),
                Reference::to(ConversionService::REFERENCE_NAME),
                DefaultHeaderMapper::createAllHeadersMapping()->getDefinition(),
                Reference::to(EventMapper::class),
            ])
        );
    }

    /**
     * @return array<string, AnnotatedFinding[]>
     */
    public function getCombinedCommandAndEventHandlersPerAggregate(): array
    {
        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlers as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][] = $registration;
        }

        return $aggregateCommandOrEventHandlers;
    }

    public function registerForDirectLoadAndSaveOfAggregate(InterfaceToCallRegistry $interfaceToCallRegistry, Configuration $messagingConfiguration): void
    {
        foreach ($this->aggregateClasses as $aggregateClass) {
            $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($aggregateClass));

            $this->registerLoadAggregate(
                $aggregateClassDefinition,
                true,
                $messagingConfiguration,
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateLoadRepositoryInputChannel($aggregateClass, true)),
                $interfaceToCallRegistry,
            );

            $this->registerLoadAggregate(
                $aggregateClassDefinition,
                false,
                $messagingConfiguration,
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateLoadRepositoryInputChannel($aggregateClass, false)),
                $interfaceToCallRegistry,
            );

            $messagingConfiguration->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass))
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], null, $interfaceToCallRegistry))
                    ->chain(SaveAggregateServiceBuilder::create())
            );

            if ($messagingConfiguration->isRunningForTest()) {
                $messagingConfiguration->registerMessageHandler(
                    MessageProcessorActivatorBuilder::create()
                        ->withInputChannelName(self::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass, forTesting: true))
                        ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], null, $interfaceToCallRegistry))
                        ->chain(SaveAggregateTestSetupServiceBuilder::create())
                );
            }
        }
    }

    public function registerBusinessRepositories(InterfaceToCallRegistry $interfaceToCallRegistry, Configuration $messagingConfiguration): void
    {
        foreach ($this->gatewayRepositoryMethods as $repositoryGateway) {
            $interface = $interfaceToCallRegistry->getFor($repositoryGateway->getClassName(), $repositoryGateway->getMethodName());
            Assert::isTrue($interface->getReturnType()->isClassNotInterface() || $interface->getReturnType()->isVoid(), 'Repository should have return type of Aggregate class or void if is save method: ' . $repositoryGateway);

            if ($interface->getReturnType()->isVoid()) {
                Assert::isTrue($interface->hasFirstParameter(), 'Saving repository should have at least one parameter for aggregate: ' . $repositoryGateway);

                if ($interface->hasMethodAnnotation(Type::attribute(RelatedAggregate::class))) {
                    Assert::isTrue($interface->hasSecondParameter(), 'Saving repository should have first parameter as identifier and second as array of events in: ' . $repositoryGateway);

                    /** @var RelatedAggregate $relatedAggregate */
                    $relatedAggregate = $interface->getSingleMethodAnnotationOf(Type::attribute(RelatedAggregate::class));
                    Assert::isTrue(in_array($relatedAggregate->getClassName(), $this->aggregateClasses), sprintf('Repository for aggregate %s:%s is registered for unknown Aggregate: %s. Have you forgot to add Class or register specific Namespaces?', $repositoryGateway->getClassName(), $repositoryGateway->getMethodName(), $relatedAggregate->getClassName()));
                    $requestChannel = self::getRegisterAggregateSaveRepositoryInputChannel($relatedAggregate->getClassName());

                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($relatedAggregate->getClassName()));

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameterName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER),
                        GatewayHeaderBuilder::create($interface->getSecondParameter()->getName(), AggregateMessage::TARGET_VERSION),
                        GatewayPayloadBuilder::create($interface->getThirdParameter()),
                        GatewayHeaderValueBuilder::create(AggregateMessage::CALLED_AGGREGATE_CLASS, $aggregateClassDefinition->getClassType()->toString()),
                    ];
                } else {
                    Assert::isTrue($interface->getFirstParameter()->getTypeDescriptor()->isClassNotInterface(), 'Saving repository should type hint for Aggregate or if is Event Sourcing make use of RelatedAggregate attribute in: ' . $repositoryGateway);
                    Assert::isTrue(in_array($interface->getFirstParameter()->getTypeDescriptor()->toString(), $this->aggregateClasses), sprintf('Repository for aggregate %s:%s is registered for unknown Aggregate: %s. Have you forgot to add Class or register specific Namespaces?', $repositoryGateway->getClassName(), $repositoryGateway->getMethodName(), $interface->getFirstParameter()->getTypeDescriptor()->toString()));
                    $requestChannel = self::getRegisterAggregateSaveRepositoryInputChannel($interface->getFirstParameter()->getTypeDescriptor()->toString());

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::CALLED_AGGREGATE_INSTANCE),
                        GatewayHeaderValueBuilder::create(AggregateMessage::CALLED_AGGREGATE_CLASS, $interface->getFirstParameter()->getTypeHint()),
                    ];
                }
            } else {
                Assert::isTrue($interface->hasFirstParameter(), 'Fetching repository should have at least one parameter for identifiers: ' . $repositoryGateway);
                Assert::isTrue(in_array($interface->getReturnType()->toString(), $this->aggregateClasses), sprintf('Repository for aggregate %s:%s is registered for unknown Aggregate: %s. Have you forgot to add Class or register specific Namespaces?', $repositoryGateway->getClassName(), $repositoryGateway->getMethodName(), $interface->getReturnType()->toString()));

                $requestChannel = self::getRegisterAggregateLoadRepositoryInputChannel($interface->getReturnType()->toString(), $interface->canItReturnNull());
                $gatewayParameterConverters = [GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)];
            }

            $messagingConfiguration->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getMethodName(),
                    $requestChannel
                )->withParameterConverters($gatewayParameterConverters)
            );
        }
    }

    public function handleRoutingEvent(RoutingEvent $event): void
    {
        $registration = $event->getRegistration();

        if (! $registration->hasAnnotation(Aggregate::class)) {
            return;
        }

        $messagingConfiguration = $event->getBusRoutingMapBuilder()->getMessagingConfiguration();

        Assert::notNull($messagingConfiguration, 'RoutingEvent should be handled with messaging configuration, but it is null. Did you forget to pass it?');

        if ($registration->isMagicMethod()) {
            $attribute = $registration->getAnnotationForMethod();
            $handlerType = match (true) {
                $attribute instanceof CommandHandler => 'command handler',
                $attribute instanceof EventHandler => 'event handler',
                $attribute instanceof QueryHandler => 'query handler',
                default => 'handler',
            };
            throw ConfigurationException::create(sprintf('%s::%s cannot be annotated as %s', $registration->getClassName(), $registration->getMethodName(), $handlerType));
        }

        if ($registration->getAnnotationForMethod() instanceof QueryHandler) {
            $this->registerAggregateQueryHandler($registration, $event->getDestinationChannel(), $messagingConfiguration);
            return;
        }

        if (\in_array($event->getDestinationChannel(), $this->channelsToCancel, true)) {
            // Cancel the route to the action channel, as it is already bridged inside factory method
            $event->cancel();
        } elseif (isset($this->channelsToBridge[$event->getDestinationChannel()])) {
            // Register a router to bridge factory method and action method
            $this->registerRoutedFactoryHandler($registration, $this->channelsToBridge[$event->getDestinationChannel()], $event->getDestinationChannel(), $messagingConfiguration);
        } else {
            // Normal handler registration
            $this->registerCommandHandler($registration, $event->getDestinationChannel(), $messagingConfiguration);
        }
    }

    private function registerCommandHandler(AnnotatedFinding $registration, string $destinationChannel, Configuration $messagingConfiguration): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        /** @var CommandHandler|EventHandler $annotation */
        $annotation = $registration->getAnnotationForMethod();

        $endpointId            = $annotation->getEndpointId();
        $dropMessageOnNotFound = $annotation->isDropMessageOnNotFound();

        $relatedClassInterface = $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $isFactoryMethod       = $relatedClassInterface->isFactoryMethod();
        $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);
        $aggregateClassDefinition = $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($registration->getClassName()));
        $handledPayloadType = MessageHandlerRoutingModule::getFirstParameterClassIfAny($registration, $this->interfaceToCallRegistry);
        $handledPayloadType = $handledPayloadType ? $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($handledPayloadType)) : null;

        // This is executed before sending to async channel
        $aggregateIdentifierHandlerPreCheck = MessageProcessorActivatorBuilder::create()
            ->withInputChannelName($destinationChannel)
            ->withOutputMessageChannel($connectionChannel = $destinationChannel . '-connection')
            ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $annotation->getIdentifierMapping(), $handledPayloadType, $this->interfaceToCallRegistry))
        ;
        $messagingConfiguration->registerMessageHandler($aggregateIdentifierHandlerPreCheck);

        $serviceActivatorHandler = MessageProcessorActivatorBuilder::create()
            ->withEndpointId($endpointId)
            ->withInputChannelName($connectionChannel)
            ->withOutputMessageChannel($annotation->getOutputChannelName())
            ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
            ->chain(TransformerProcessorBuilder::create(
                TransformerBuilder::createHeaderEnricher([
                    AggregateMessage::CALLED_AGGREGATE_CLASS => $registration->getClassName(),
                ])
            ));

        if (! $isFactoryMethod) {
            $serviceActivatorHandler
                /** @TODO Ecotone 2.0 (remove) this. For backward compatibility when messages without AggregateMessage::AGGREGATE_ID is not available*/
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $annotation->getIdentifierMapping(), $handledPayloadType, $this->interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound ? LoadAggregateMode::createDropMessageOnNotFound() : LoadAggregateMode::createThrowOnNotFound())
                );
        }

        $serviceActivatorHandler
            ->chainInterceptedProcessor(
                CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), true, $this->interfaceToCallRegistry)
                    ->withMethodParameterConverters($parameterConverters)
            )
            ->chain(
                SaveAggregateServiceBuilder::create()
            );

        $messagingConfiguration->registerMessageHandler($serviceActivatorHandler);
    }

    public function registerRoutedFactoryHandler(AnnotatedFinding $factoryRegistration, AnnotatedFinding $actionRegistration, string $destinationChannel, Configuration $messagingConfiguration): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $aggregateClassDefinition = $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($factoryRegistration->getClassName()));

        // factory processor
        /** @var CommandHandler|EventHandler $factoryAnnotation */
        $factoryAnnotation = $factoryRegistration->getAnnotationForMethod();

        $factoryInterface = $this->interfaceToCallRegistry->getFor($factoryRegistration->getClassName(), $factoryRegistration->getMethodName());
        $factoryHandledPayloadType        = MessageHandlerRoutingModule::getFirstParameterClassIfAny($factoryRegistration, $this->interfaceToCallRegistry);
        $factoryHandledPayloadType        = $factoryHandledPayloadType ? $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($factoryHandledPayloadType)) : null;
        $factoryIdentifierMetadataMapping = $factoryAnnotation->identifierMetadataMapping;
        $factoryIdentifierMapping = $factoryAnnotation->identifierMapping;
        $factoryParameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($factoryInterface);

        $factoryProcessor = ChainedMessageProcessorBuilder::create()->chainInterceptedProcessor(
            CallAggregateServiceBuilder::create($aggregateClassDefinition, $factoryRegistration->getMethodName(), true, $this->interfaceToCallRegistry)
                ->withMethodParameterConverters($factoryParameterConverters)
        )
            ->withRequiredInterceptorNames($factoryAnnotation->getRequiredInterceptorNames());

        // action processor
        /** @var CommandHandler|EventHandler $actionAnnotation */
        $actionAnnotation = $actionRegistration->getAnnotationForMethod();
        $actionInterface = $this->interfaceToCallRegistry->getFor($actionRegistration->getClassName(), $actionRegistration->getMethodName());
        $actionParameterConverters = $parameterConverterAnnotationFactory->createParameterWithDefaults($actionInterface);
        $actionProcessor = ChainedMessageProcessorBuilder::create()->chainInterceptedProcessor(
            CallAggregateServiceBuilder::create($aggregateClassDefinition, $actionRegistration->getMethodName(), true, $this->interfaceToCallRegistry)
                ->withMethodParameterConverters($actionParameterConverters)
        )
            ->withRequiredInterceptorNames($actionAnnotation->getRequiredInterceptorNames());

        // This is executed before sending to async channel
        $aggregateIdentifierHandlerPreCheck = MessageProcessorActivatorBuilder::create()
            ->withInputChannelName($destinationChannel)
            ->withOutputMessageChannel($connectionChannel = $destinationChannel . '-connection')
            ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryIdentifierMapping, $factoryHandledPayloadType, $this->interfaceToCallRegistry))
        ;
        $messagingConfiguration->registerMessageHandler($aggregateIdentifierHandlerPreCheck);

        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::create()
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($factoryAnnotation->getOutputChannelName())
                // factory endpoint name is used. action endpoint name is not used
                ->withEndpointId($factoryAnnotation->getEndpointId())
                ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($factoryRegistration)->toAttributeDefinition()])
                ->chain(TransformerProcessorBuilder::create(
                    TransformerBuilder::createHeaderEnricher([
                        AggregateMessage::CALLED_AGGREGATE_CLASS => $factoryRegistration->getClassName(),
                    ])
                ))
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryIdentifierMapping, $factoryHandledPayloadType, $this->interfaceToCallRegistry))
                ->chainInterceptedProcessor(
                    // Registering this processor as intercepted causes before and after interceptors to be executed twice, once for loading aggregate and once for calling action or factory method.
                    // This is required to avoid B/C breaks where before interceptor could add identifier metadata to the message.
                    // First interception uses factory method pointcut only, second interception uses factory or action interface
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $factoryRegistration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound())
                )
                ->chain(
                    RouterProcessorBuilder::createHeaderExistsRouter(
                        AggregateMessage::CALLED_AGGREGATE_INSTANCE,
                        $actionProcessor,
                        $factoryProcessor,
                    )
                )
                ->chain(
                    SaveAggregateServiceBuilder::create()
                )
        );

        $actionInputChannelName = match(true) {
            $actionAnnotation instanceof EventHandler => $actionAnnotation->getListenTo(),
            $actionAnnotation instanceof CommandHandler => $actionAnnotation->getInputChannelName(),
            default => throw ConfigurationException::create('Action handler should be either CommandHandler or EventHandler, but got ' . $actionAnnotation::class),
        };
        if (! $actionInputChannelName) {
            // this is a fake channel to avoid B/C break when action handler has an Asynchronous attribute (only the factory method should have the attribute)
            $actionInputChannelName = Uuid::uuid4();
        }
        // Add a bridge from the action channel to the factory+action channel
        if (! ($factoryAnnotation instanceof CommandHandler && $actionInputChannelName === $factoryAnnotation->getInputChannelName())) {
            $messagingConfiguration->registerMessageHandler(
                BridgeBuilder::create()
                    ->withEndpointId($actionAnnotation->getEndpointId())
                    ->withInputChannelName($actionInputChannelName)
                    ->withOutputMessageChannel($destinationChannel)
                    ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($actionRegistration)->toAttributeDefinition()])
            );
        }
    }
}
