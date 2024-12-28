<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Router\RouterProcessorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerProcessorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\AggregateFlow\CallAggregate\CallAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateMode;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateClassDefinition;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Attribute\RelatedAggregate;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcingExecutor\EnterpriseAggregateMethodInvoker;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutorBuilder;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;
use Ecotone\Modelling\EventSourcingExecutor\OpenCoreAggregateMethodInvoker;
use Ecotone\Modelling\FetchAggregate;
use Ecotone\Modelling\RepositoryBuilder;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class AggregrateHandlerModule implements AnnotationModule
{
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
        private array $aggregateClasses,
        private array $aggregateClassDefinitions,
        private array $aggregateCommandHandlers,
        private array $aggregateQueryHandlers,
        private array $aggregateEventHandlers,
        private array $aggregateRepositoryReferenceNames,
        private array $gatewayRepositoryMethods
    ) {
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
            $aggregateRepositoryReferenceNames[] = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $aggregateRepositoryClass);
        }

        $aggregateClasses = $annotationRegistrationService->findAnnotatedClasses(Aggregate::class);
        $aggregateClassDefinitions = [];
        foreach ($aggregateClasses as $aggregateClass) {
            $aggregateClassDefinitions[$aggregateClass] = AggregateDefinitionResolver::resolve($aggregateClass, $interfaceToCallRegistry);
        }

        return new self(
            $aggregateClasses,
            $aggregateClassDefinitions,
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class),
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
            $annotationRegistrationService->findAnnotatedMethods(Repository::class)
        );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof RepositoryBuilder
            ||
            $extensionObject instanceof BaseEventSourcingConfiguration;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->initialization($messagingConfiguration, $interfaceToCallRegistry);

        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        foreach ($moduleExtensions as $aggregateRepositoryBuilder) {
            if ($aggregateRepositoryBuilder instanceof RepositoryBuilder) {
                $referenceId = Uuid::uuid4()->toString();
                $moduleReferenceSearchService->store($referenceId, $aggregateRepositoryBuilder);
                $this->aggregateRepositoryReferenceNames[$referenceId] = $referenceId;
            }
        }
        $baseEventSourcingConfiguration = new BaseEventSourcingConfiguration();
        foreach ($moduleExtensions as $moduleExtension) {
            if ($moduleExtension instanceof BaseEventSourcingConfiguration) {
                $baseEventSourcingConfiguration = $moduleExtension;
            }
        }

        $this->registerForDirectLoadAndSaveOfAggregate($interfaceToCallRegistry, $messagingConfiguration, $baseEventSourcingConfiguration);
        $this->registerBusinessRepositories($interfaceToCallRegistry, $messagingConfiguration);

        foreach ($this->aggregateQueryHandlers as $registration) {
            $this->registerAggregateQueryHandler($registration, $interfaceToCallRegistry, $parameterConverterAnnotationFactory, $messagingConfiguration);
        }

        foreach ($this->getCombinedCommandAndEventHandlers($interfaceToCallRegistry, $messagingConfiguration) as $channelNameRegistrations) {
            foreach ($channelNameRegistrations as $channelName => $registrations) {
                $this->registerAggregateCommandHandler($messagingConfiguration, $interfaceToCallRegistry, $this->aggregateRepositoryReferenceNames, $registrations, $channelName, $baseEventSourcingConfiguration);
            }
        }
    }

    /**
     * @var AnnotatedDefinition[] $registrations
     */
    private function registerAggregateCommandHandler(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry, array $aggregateRepositoryReferenceNames, array $registrations, string $inputChannelNameForRouting, BaseEventSourcingConfiguration $baseEventSourcingConfiguration): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $registration = reset($registrations);

        $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($registration->getClassName()));
        if (count($registrations) > 2 && $registration->getAnnotationForMethod() instanceof CommandHandler) {
            throw new InvalidArgumentException("Command Handler registers multiple times on {$registration->getClassName()}::{$registration->getMethodName()} method. You may register same Command Handler for action and factory method method maximum.");
        }

        $actionChannels                    = [];
        $factoryChannel                   = null;
        $factoryHandledPayloadType        = null;
        $factoryIdentifierMetadataMapping = [];
        $factoryIdentifierMapping = [];
        foreach ($registrations as $registration) {
            $channel = MessageHandlerRoutingModule::getExecutionMessageHandlerChannel($registration);
            if ((new ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic()) {
                Assert::null($factoryChannel, "Trying to register factory method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelNameForRouting}");
                $factoryChannel                   = $channel;
                $factoryHandledPayloadType        = MessageHandlerRoutingModule::getFirstParameterClassIfAny($registration, $interfaceToCallRegistry);
                $factoryHandledPayloadType        = $factoryHandledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($factoryHandledPayloadType)) : null;
                $factoryIdentifierMetadataMapping = $registration->getAnnotationForMethod()->identifierMetadataMapping;
                $factoryIdentifierMapping = $registration->getAnnotationForMethod()->identifierMapping;
            } else {
                if ($actionChannels !== [] && $registration->getAnnotationForMethod() instanceof CommandHandler) {
                    throw \Ecotone\Messaging\Support\InvalidArgumentException::create("Trying to register action method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelNameForRouting}");
                }

                $actionChannels[] = $channel;
            }
        }

        $hasFactoryAndActionRedirect = $actionChannels !== [] && $factoryChannel !== null;
        if ($hasFactoryAndActionRedirect) {
            Assert::isTrue(count($actionChannels) <= 1, "Message Handlers on Aggregate and Saga can be used either for single factory method and single action method together, or for multiple actions methods in {$aggregateClassDefinition->getClassType()->toString()}");

            $configuration->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName($inputChannelNameForRouting)
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryIdentifierMapping, $factoryHandledPayloadType, $interfaceToCallRegistry))
                    ->chainInterceptedProcessor(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->chain(RouterProcessorBuilder::createHeaderExistsRouter(AggregateMessage::CALLED_AGGREGATE_INSTANCE, $actionChannels[0], $factoryChannel))
            );
        }

        foreach ($registrations as $registration) {
            /** @var CommandHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $endpointId            = $annotation->getEndpointId();
            $dropMessageOnNotFound = $annotation->isDropMessageOnNotFound();

            $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $isFactoryMethod       = $relatedClassInterface->isFactoryMethod();
            $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);
            $connectionChannel     = $hasFactoryAndActionRedirect
                ? ($isFactoryMethod ? $factoryChannel : $actionChannels[0])
                : MessageHandlerRoutingModule::getExecutionMessageHandlerChannel($registration);
            if (! $hasFactoryAndActionRedirect) {
                $configuration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withInputChannelName($inputChannelNameForRouting)
                        ->withOutputMessageChannel($connectionChannel)
                        ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($registration)->toAttributeDefinition()])
                );
            }

            $serviceActivatorHandler = MessageProcessorActivatorBuilder::create()
                ->withEndpointId($endpointId)
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($annotation->getOutputChannelName())
                ->chain(TransformerProcessorBuilder::create(
                    TransformerBuilder::createHeaderEnricher([
                        AggregateMessage::CALLED_AGGREGATE_CLASS => $registration->getClassName(),
                    ])
                ));

            if (! $isFactoryMethod) {
                $handledPayloadType = MessageHandlerRoutingModule::getFirstParameterClassIfAny($registration, $interfaceToCallRegistry);
                $handledPayloadType = $handledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($handledPayloadType)) : null;
                $serviceActivatorHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $annotation->getIdentifierMapping(), $handledPayloadType, $interfaceToCallRegistry))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound ? LoadAggregateMode::createDropMessageOnNotFound() : LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    );
            }

            $serviceActivatorHandler
                ->chainInterceptedProcessor(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), true, $interfaceToCallRegistry)
                        ->withMethodParameterConverters($parameterConverters)
                )
                ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames());

            $serviceActivatorHandler->chain(
                SaveAggregateServiceBuilder::create(
                    $baseEventSourcingConfiguration
                )
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
            );

            $configuration->registerMessageHandler($serviceActivatorHandler);
        }
    }

    private function registerAggregateQueryHandler(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory, Configuration $configuration): void
    {
        /** @var QueryHandler $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $relatedClassInterface    = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters      = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);
        $endpointChannelName      = MessageHandlerRoutingModule::getExecutionMessageHandlerChannel($registration);
        $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($registration->getClassName()));
        $handledPayloadType       = MessageHandlerRoutingModule::getFirstParameterClassIfAny($registration, $interfaceToCallRegistry);
        $handledPayloadType       = $handledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($handledPayloadType)) : null;


        $inputChannelName = MessageHandlerRoutingModule::getRoutingInputMessageChannelFor($registration, $interfaceToCallRegistry);
        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $configuration->registerMessageHandler(
            BridgeBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($endpointChannelName)
        );

        $configuration->registerMessageHandler(
            MessageProcessorActivatorBuilder::create()
                ->withInputChannelName($endpointChannelName)
                ->withOutputMessageChannel($annotationForMethod->getOutputChannelName())
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], $handledPayloadType, $interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chainInterceptedProcessor(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false, $interfaceToCallRegistry)
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
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $methodName, null, $canReturnNull ? LoadAggregateMode::createContinueOnNotFound() : LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
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

    public static function getRegisterAggregateSaveRepositoryInputChannel(string $className): string
    {
        return self::getAggregateRepositoryInputChannel($className, '.will_save', true, false);
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

        foreach ($this->aggregateCommandHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as command handler', $registration->getClassName(), $registration->getMethodName()));
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as event handler', $registration->getClassName(), $registration->getMethodName()));
        }

        foreach ($this->aggregateQueryHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as query handler', $registration->getClassName(), $registration->getMethodName()));
        }

        $eventSourcingExecutors = [];
        foreach ($this->aggregateClassDefinitions as $aggregateClassDefinition) {
            if ($aggregateClassDefinition->isEventSourced()) {
                $eventSourcingExecutors[$aggregateClassDefinition->getClassName()] = EventSourcingHandlerExecutorBuilder::createFor(
                    $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($aggregateClassDefinition->getClassName())),
                    $aggregateClassDefinition->isEventSourced(),
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
            ])
        );
    }

    /**
     * @return array<string, array<string, AnnotatedFinding[]>>
     */
    public function getCombinedCommandAndEventHandlers(InterfaceToCallRegistry $interfaceToCallRegistry, Configuration $messagingConfiguration): array
    {
        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlers as $registration) {
            $channelName = MessageHandlerRoutingModule::getRoutingInputMessageChannelFor($registration, $interfaceToCallRegistry);
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $channelName = MessageHandlerRoutingModule::getRoutingInputMessageChannelForEventHandler($registration, $interfaceToCallRegistry);
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        return $aggregateCommandOrEventHandlers;
    }

    public function registerForDirectLoadAndSaveOfAggregate(InterfaceToCallRegistry $interfaceToCallRegistry, Configuration $messagingConfiguration, BaseEventSourcingConfiguration $baseEventSourcingConfiguration): void
    {
        foreach ($this->aggregateClasses as $aggregateClass) {
            $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($aggregateClass));

            $this->registerLoadAggregate(
                $aggregateClassDefinition,
                true,
                $messagingConfiguration,
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateLoadRepositoryInputChannel($aggregateClass, true)),
                $interfaceToCallRegistry
            );

            $this->registerLoadAggregate(
                $aggregateClassDefinition,
                false,
                $messagingConfiguration,
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateLoadRepositoryInputChannel($aggregateClass, false)),
                $interfaceToCallRegistry
            );

            $messagingConfiguration->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass))
                    ->chain(
                        SaveAggregateServiceBuilder::create($baseEventSourcingConfiguration)
                            ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                    )
            );

            if ($messagingConfiguration->isRunningForTest()) {
                $messagingConfiguration->registerMessageHandler(
                    MessageProcessorActivatorBuilder::create()
                        ->withInputChannelName(self::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass) . '.test_setup_state')
                        ->chain(
                            SaveAggregateServiceBuilder::create($baseEventSourcingConfiguration)
                                ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                                ->withPublishEvents(false)
                        )
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

                if ($interface->hasMethodAnnotation(TypeDescriptor::create(RelatedAggregate::class))) {
                    Assert::isTrue($interface->hasSecondParameter(), 'Saving repository should have first parameter as identifier and second as array of events in: ' . $repositoryGateway);

                    /** @var RelatedAggregate $relatedAggregate */
                    $relatedAggregate = $interface->getSingleMethodAnnotationOf(TypeDescriptor::create(RelatedAggregate::class));
                    Assert::isTrue(in_array($relatedAggregate->getClassName(), $this->aggregateClasses), sprintf('Repository for aggregate %s:%s is registered for unknown Aggregate: %s. Have you forgot to add Class or register specific Namespaces?', $repositoryGateway->getClassName(), $repositoryGateway->getMethodName(), $relatedAggregate->getClassName()));
                    $requestChannel = self::getRegisterAggregateSaveRepositoryInputChannel($relatedAggregate->getClassName());

                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($relatedAggregate->getClassName()));

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameterName(), AggregateMessage::AGGREGATE_ID),
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
}
