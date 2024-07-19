<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\StreamBasedSource;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\AggregateFlow\CallAggregate\CallAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateMode;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\PublishEvents\PublishAggregateEventsServiceBuilder;
use Ecotone\Modelling\AggregateFlow\ResolveAggregate\ResolveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\ResolveEvents\ResolveAggregateEventsServiceBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\ChangingHeaders;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Attribute\RelatedAggregate;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\FetchAggregate;
use Ecotone\Modelling\RepositoryBuilder;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use ReflectionParameter;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class ModellingHandlerModule implements AnnotationModule
{
    private ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory;
    /**
     * @var AnnotatedFinding[]
     */
    private array $aggregateCommandHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $serviceCommandHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $aggregateQueryHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $serviceQueryHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $aggregateEventHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $serviceEventHandlers;
    /**
     * @var AnnotatedFinding[]
     */
    private array $gatewayRepositoryMethods;
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames;

    private function __construct(
        ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory,
        array $aggregateCommandHandlerRegistrations,
        array $serviceCommandHandlersRegistrations,
        array $aggregateQueryHandlerRegistrations,
        array $serviceQueryHandlerRegistrations,
        array $aggregateEventHandlers,
        array $serviceEventHandlers,
        array $aggregateRepositoryReferenceNames,
        array $gatewayRepositoryMethods
    ) {
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
        $this->aggregateCommandHandlers            = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlers              = $aggregateQueryHandlerRegistrations;
        $this->serviceCommandHandlers  = $serviceCommandHandlersRegistrations;
        $this->serviceQueryHandlers     = $serviceQueryHandlerRegistrations;
        $this->aggregateEventHandlers               = $aggregateEventHandlers;
        $this->serviceEventHandlers                 = $serviceEventHandlers;
        $this->aggregateRepositoryReferenceNames    = $aggregateRepositoryReferenceNames;
        $this->gatewayRepositoryMethods = $gatewayRepositoryMethods;
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

        return new self(
            ParameterConverterAnnotationFactory::create(),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            $aggregateRepositoryReferenceNames,
            $annotationRegistrationService->findAnnotatedMethods(Repository::class)
        );
    }

    public static function getMessagePayloadTypeFor(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): string
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());

        if ($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)) || $interfaceToCall->hasNoParameters()) {
            return TypeDescriptor::ARRAY;
        }

        $firstParameterType = $interfaceToCall->getFirstParameter()->getTypeDescriptor();

        if ($firstParameterType->isClassOrInterface() && ! $firstParameterType->isClassOfType(TypeDescriptor::create(Message::class))) {
            $reflectionParameter = new ReflectionParameter([$registration->getClassName(), $registration->getMethodName()], 0);

            foreach ($reflectionParameter->getAttributes() as $attribute) {
                if (in_array($attribute->getName(), [ConfigurationVariable::class, Header::class, Headers::class, Reference::class])) {
                    return TypeDescriptor::ARRAY;
                }
            }

            return $firstParameterType;
        }

        return TypeDescriptor::ARRAY;
    }

    public static function getHandlerChannel(AnnotatedFinding $registration): string
    {
        /** @var EndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        return $annotationForMethod->getEndpointId() . '.target';
    }

    public static function getPayloadClassIfAny(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration, $interfaceToCallRegistry));

        if ($type->isClassOrInterface() && ! $type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type->toString();
        }

        return null;
    }

    public static function getEventPayloadClasses(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration, $interfaceToCallRegistry));
        if ($type->isClassOrInterface() && ! $type->isClassOfType(TypeDescriptor::create(Message::class))) {
            if ($type->isUnionType()) {
                return array_map(fn (TypeDescriptor $type) => $type->toString(), $type->getUnionTypes());
            }

            return [$type->toString()];
        }

        return [];
    }

    public static function hasMessageNameDefined(AnnotatedFinding $registration): bool
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $annotationForMethod->getListenTo();
        } else {
            $inputChannelName = $annotationForMethod->getInputChannelName();
        }

        return $inputChannelName ? true : false;
    }

    public static function getNamedMessageChannelForEventHandler(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $inputChannelName = null;
        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $annotationForMethod->getListenTo();
        }

        if (! $inputChannelName) {
            $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            if ($interfaceToCall->hasNoParameters()) {
                throw ConfigurationException::create("Missing command class or listen routing for {$registration}.");
            }
            $inputChannelName = $interfaceToCall->getFirstParameterTypeHint();
        }

        return $inputChannelName;
    }

    public static function getNamedMessageChannelFor(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $annotationForMethod->getListenTo();
        } else {
            $inputChannelName = $annotationForMethod->getInputChannelName();
        }

        if (! $inputChannelName) {
            $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            if ($interfaceToCall->hasNoParameters()) {
                throw ConfigurationException::create("Missing class type hint or routing key for {$registration}.");
            }
            if ($interfaceToCall->getFirstParameter()->getTypeDescriptor()->isUnionType()) {
                throw ConfigurationException::create("Query and Command handlers can not be registered with union Command type in {$registration}");
            }
            $inputChannelName = $interfaceToCall->getFirstParameterTypeHint();
        }

        return $inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof RepositoryBuilder
            ||
            $extensionObject instanceof BaseEventSourcingConfiguration
            ||
            $extensionObject instanceof RegisterAggregateRepositoryChannels;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->aggregateCommandHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as command handler', $registration->getClassName(), $registration->getMethodName()));
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as event handler', $registration->getClassName(), $registration->getMethodName()));
        }

        foreach ($this->aggregateQueryHandlers as $registration) {
            Assert::isFalse($registration->isMagicMethod(), sprintf('%s::%s cannot be annotated as query handler', $registration->getClassName(), $registration->getMethodName()));
        }

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

        foreach ($this->gatewayRepositoryMethods as $repositoryGateway) {
            $interface = $interfaceToCallRegistry->getFor($repositoryGateway->getClassName(), $repositoryGateway->getMethodName());
            Assert::isTrue($interface->getReturnType()->isClassNotInterface() || $interface->getReturnType()->isVoid(), 'Repository should have return type of Aggregate class or void if is save method: ' . $repositoryGateway);

            $inputChannelName = self::getAggregateRepositoryInputChannel($repositoryGateway->getClassName(), $repositoryGateway->getMethodName(), $interface->getReturnType()->isVoid(), $interface->canItReturnNull());

            $chainMessageHandlerBuilder = ChainMessageHandlerBuilder::create()
                ->withInputChannelName($inputChannelName);
            if ($interface->getReturnType()->isVoid()) {
                Assert::isTrue($interface->hasFirstParameter(), 'Saving repository should have at least one parameter for aggregate: ' . $repositoryGateway);

                if ($interface->hasMethodAnnotation(TypeDescriptor::create(RelatedAggregate::class))) {
                    Assert::isTrue($interface->hasSecondParameter(), 'Saving repository should have first parameter as identifier and second as array of events in: ' . $repositoryGateway);

                    /** @var RelatedAggregate $relatedAggregate */
                    $relatedAggregate = $interface->getSingleMethodAnnotationOf(TypeDescriptor::create(RelatedAggregate::class));

                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($relatedAggregate->getClassName()));

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameterName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER),
                        GatewayHeaderBuilder::create($interface->getSecondParameter()->getName(), AggregateMessage::TARGET_VERSION),
                        GatewayPayloadBuilder::create($interface->getThirdParameter()),
                        GatewayHeaderValueBuilder::create(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregateClassDefinition->getClassType()->toString()),
                        GatewayHeaderValueBuilder::create(AggregateMessage::RESULT_AGGREGATE_OBJECT, $aggregateClassDefinition->getClassType()->toString()),
                    ];

                    $chainMessageHandlerBuilder = $chainMessageHandlerBuilder
                        ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], null, $interfaceToCallRegistry));
                } else {
                    Assert::isTrue($interface->getFirstParameter()->getTypeDescriptor()->isClassNotInterface(), 'Saving repository should type hint for Aggregate or if is Event Sourcing make use of RelatedAggregate attribute in: ' . $repositoryGateway);
                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor($interface->getFirstParameter()->getTypeDescriptor());

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::CALLED_AGGREGATE_OBJECT),
                        GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::RESULT_AGGREGATE_OBJECT),
                    ];
                }

                $this->registerSaveAggregate($aggregateClassDefinition, $messagingConfiguration, $chainMessageHandlerBuilder, $interfaceToCallRegistry, $baseEventSourcingConfiguration, $inputChannelName);
            } else {
                Assert::isTrue($interface->hasFirstParameter(), 'Fetching repository should have at least one parameter for identifiers: ' . $repositoryGateway);
                $this->registerLoadAggregate(
                    $interfaceToCallRegistry->getClassDefinitionFor($interface->getReturnType()),
                    $interface->canItReturnNull(),
                    $messagingConfiguration,
                    $chainMessageHandlerBuilder,
                    $interfaceToCallRegistry
                );

                $gatewayParameterConverters = [GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)];
            }

            $messagingConfiguration->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getMethodName(),
                    $inputChannelName
                )->withParameterConverters($gatewayParameterConverters)
            );
        }

        /** @var RegisterAggregateRepositoryChannels $registerAggregateChannel */
        foreach (ExtensionObjectResolver::resolve(RegisterAggregateRepositoryChannels::class, $moduleExtensions) as $registerAggregateChannel) {
            $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($registerAggregateChannel->getClassName()));

            $this->registerLoadAggregate(
                $aggregateClassDefinition,
                false,
                $messagingConfiguration,
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateLoadRepositoryInputChannel($registerAggregateChannel->getClassName())),
                $interfaceToCallRegistry
            );

            $this->registerSaveAggregate(
                $aggregateClassDefinition,
                $messagingConfiguration,
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName(self::getRegisterAggregateSaveRepositoryInputChannel($registerAggregateChannel->getClassName()))
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], null, $interfaceToCallRegistry)),
                $interfaceToCallRegistry,
                $baseEventSourcingConfiguration,
                self::getRegisterAggregateSaveRepositoryInputChannel($registerAggregateChannel->getClassName())
            );
        }

        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlers as $registration) {
            $channelName = self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $channelName = self::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        foreach ($aggregateCommandOrEventHandlers as $channelNameRegistrations) {
            foreach ($channelNameRegistrations as $channelName => $registrations) {
                $this->registerAggregateCommandHandler($messagingConfiguration, $interfaceToCallRegistry, $this->aggregateRepositoryReferenceNames, $registrations, $channelName, $baseEventSourcingConfiguration);
            }
        }

        foreach ($this->aggregateQueryHandlers as $registration) {
            $this->registerAggregateQueryHandler($registration, $interfaceToCallRegistry, $parameterConverterAnnotationFactory, $messagingConfiguration);
        }

        foreach ($this->serviceCommandHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, false);
        }
        foreach ($this->serviceQueryHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, false);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, $registration->hasClassAnnotation(StreamBasedSource::class));
        }
    }

    /**
     * @var AnnotatedDefinition[] $registrations
     */
    private function registerAggregateCommandHandler(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry, array $aggregateRepositoryReferenceNames, array $registrations, string $messageChannelName, BaseEventSourcingConfiguration $baseEventSourcingConfiguration): void
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
            $channel = self::getHandlerChannel($registration);
            if ((new ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic()) {
                Assert::null($factoryChannel, "Trying to register factory method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$messageChannelName}");
                $factoryChannel                   = $channel;
                $factoryHandledPayloadType        = self::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
                $factoryHandledPayloadType        = $factoryHandledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($factoryHandledPayloadType)) : null;
                $factoryIdentifierMetadataMapping = $registration->getAnnotationForMethod()->identifierMetadataMapping;
                $factoryIdentifierMapping = $registration->getAnnotationForMethod()->identifierMapping;
            } else {
                if ($actionChannels !== [] && $registration->getAnnotationForMethod() instanceof CommandHandler) {
                    throw \Ecotone\Messaging\Support\InvalidArgumentException::create("Trying to register action method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$messageChannelName}");
                }

                $actionChannels[] = $channel;
            }
        }

        $hasFactoryAndActionRedirect = $actionChannels !== [] && $factoryChannel !== null;
        if ($hasFactoryAndActionRedirect) {
            Assert::isTrue(count($actionChannels) <= 1, "Message Handlers on Aggregate and Saga can be used either for single factory method and single action method together, or for multiple actions methods in {$aggregateClassDefinition->getClassType()->toString()}");

            $messageChannelNameRouter = Uuid::uuid4()->toString();
            $configuration->registerMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($messageChannelName)
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryIdentifierMapping, $factoryHandledPayloadType, $interfaceToCallRegistry))
                    ->chainInterceptedHandler(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->withOutputMessageChannel($messageChannelNameRouter)
            );

            $configuration->registerMessageHandler(
                RouterBuilder::createHeaderMappingRouter(AggregateMessage::AGGREGATE_OBJECT_EXISTS, [true => $actionChannels[0], false => $factoryChannel])
                    ->withInputChannelName($messageChannelNameRouter)
            );
        }

        foreach ($registrations as $registration) {
            /** @var CommandHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $endpointId            = $annotation->getEndpointId();
            $dropMessageOnNotFound = $annotation->isDropMessageOnNotFound();

            $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $isFactoryMethod       = $relatedClassInterface->isFactoryMethod();
            $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
            $connectionChannel     = $hasFactoryAndActionRedirect
                ? ($isFactoryMethod ? $factoryChannel : $actionChannels[0])
                : self::getHandlerChannel($registration);
            if (! $hasFactoryAndActionRedirect) {
                $configuration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withInputChannelName($messageChannelName)
                        ->withOutputMessageChannel($connectionChannel)
                        ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($registration)->toAttributeDefinition()])
                );
            }

            $resolveEventsChannel  = $connectionChannel . 'resolve-events';
            $resolveAggregateChannel  = $connectionChannel . 'resolve-result';
            $saveChannel  = $connectionChannel . 'save';
            $publishChannel  = $connectionChannel . 'publish';
            $chainHandler = ChainMessageHandlerBuilder::create()
                ->withEndpointId($endpointId)
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($resolveEventsChannel);

            if (! $isFactoryMethod) {
                $handledPayloadType = self::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
                $handledPayloadType = $handledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($handledPayloadType)) : null;
                $chainHandler       = $chainHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $annotation->getIdentifierMapping(), $handledPayloadType, $interfaceToCallRegistry))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound ? LoadAggregateMode::createDropMessageOnNotFound() : LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    );
            }

            $chainHandler = $chainHandler
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), true, $interfaceToCallRegistry)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
                );

            $configuration->registerMessageHandler($chainHandler);
            $configuration->registerMessageHandler(
                ResolveAggregateEventsServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $interfaceToCallRegistry)
                    ->withInputChannelName($resolveEventsChannel)
                    ->withOutputMessageChannel($resolveAggregateChannel)
            );
            $configuration->registerMessageHandler(
                ResolveAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $interfaceToCallRegistry)
                    ->withInputChannelName($resolveAggregateChannel)
                    ->withOutputMessageChannel($saveChannel)
            );
            $configuration->registerMessageHandler(
                SaveAggregateServiceBuilder::create(
                    $aggregateClassDefinition,
                    $registration->getMethodName(),
                    $interfaceToCallRegistry,
                    $baseEventSourcingConfiguration
                )
                    ->withInputChannelName($saveChannel)
                    ->withOutputMessageChannel($publishChannel)
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
            );
            $configuration->registerMessageHandler(
                PublishAggregateEventsServiceBuilder::create(
                    $aggregateClassDefinition,
                    $registration->getMethodName(),
                    $interfaceToCallRegistry
                )
                    ->withInputChannelName($publishChannel)
                    ->withOutputMessageChannel($annotation->getOutputChannelName())
            );
        }
    }

    private function registerAggregateQueryHandler(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory, Configuration $configuration): void
    {
        /** @var QueryHandler $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $relatedClassInterface    = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters      = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
        $endpointChannelName      = self::getHandlerChannel($registration);
        $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($registration->getClassName()));
        $handledPayloadType       = self::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
        $handledPayloadType       = $handledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($handledPayloadType)) : null;


        $inputChannelName = self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $configuration->registerMessageHandler(
            BridgeBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($endpointChannelName)
        );

        $configuration->registerMessageHandler(
            ChainMessageHandlerBuilder::create()
                ->withInputChannelName($endpointChannelName)
                ->withOutputMessageChannel($annotationForMethod->getOutputChannelName())
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], [], $handledPayloadType, $interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false, $interfaceToCallRegistry)
                        ->withEndpointId($annotationForMethod->getEndpointId())
                        ->withMethodParameterConverters($parameterConverters)
                        ->withRequiredInterceptorNames($annotationForMethod->getRequiredInterceptorNames())
                )
        );
    }

    private function registerServiceHandler(string $inputChannelName, Configuration $configuration, AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry, bool $isStreamBasedSource): void
    {
        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation                    = $registration->getAnnotationForMethod();
        $endpointInputChannel                = self::getHandlerChannel($registration);
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        /**
         * We want to connect Event Handler directly to Event Bus channel only if it's not fetched from Stream Based Source.
         * This allows to connecting Event Handlers via Projection Event Handler that lead the way.
         */
        if (! $isStreamBasedSource) {
            $configuration->registerMessageHandler(
                BridgeBuilder::create()
                    ->withInputChannelName($inputChannelName)
                    ->withOutputMessageChannel($endpointInputChannel)
                    ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($registration)->toAttributeDefinition()])
            );
        }

        $handler = $registration->hasMethodAnnotation(ChangingHeaders::class)
            ? TransformerBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()))
            : ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()));

        $configuration->registerMessageHandler(
            $handler
                ->withInputChannelName($endpointInputChannel)
                ->withOutputMessageChannel($methodAnnotation->getOutputChannelName())
                ->withEndpointId($methodAnnotation->getEndpointId())
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($methodAnnotation->getRequiredInterceptorNames())
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    public static function getRegisterAggregateLoadRepositoryInputChannel(string $className): string
    {
        return self::getAggregateRepositoryInputChannel($className, 'will_load' . $className, false, false);
    }

    public static function getRegisterAggregateSaveRepositoryInputChannel(string $className): string
    {
        return self::getAggregateRepositoryInputChannel($className, 'will_save' . $className, true, false);
    }

    public static function getAggregateRepositoryInputChannel(string $className, string $methodName1, bool $isSave, bool $canReturnNull): string
    {
        return $className . $methodName1 . ($isSave ? '.save' : '.load' . ($canReturnNull ? '.nullable' : ''));
    }

    private function registerLoadAggregate(ClassDefinition $aggregateClassDefinition, bool $canReturnNull, Configuration $configuration, ChainMessageHandlerBuilder $chainMessageHandlerBuilder, InterfaceToCallRegistry $interfaceToCallRegistry): void
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
                ->chain(
                    ServiceActivatorBuilder::createWithDefinition(new Definition(FetchAggregate::class), 'fetch')
                        ->withMethodParameterConverters([
                            HeaderBuilder::createOptional('aggregate', AggregateMessage::CALLED_AGGREGATE_OBJECT),
                            HeaderBuilder::createOptional('aggregate', AggregateMessage::RESULT_AGGREGATE_OBJECT),
                        ])
                )
        );
    }

    private function registerSaveAggregate(ClassDefinition $aggregateClassDefinition, Configuration $configuration, ChainMessageHandlerBuilder|InputOutputMessageHandlerBuilder $chainMessageHandlerBuilder, InterfaceToCallRegistry $interfaceToCallRegistry, BaseEventSourcingConfiguration $baseEventSourcingConfiguration, string $inputChannelName): void
    {
        /** @TODO do not require method name in save service */
        $methodName = $aggregateClassDefinition->getPublicMethodNames() ? $aggregateClassDefinition->getPublicMethodNames()[0] : '__construct';

        $configuration->registerMessageHandler(
            $chainMessageHandlerBuilder
                ->chain(ResolveAggregateEventsServiceBuilder::create($aggregateClassDefinition, $methodName, $interfaceToCallRegistry))
                ->chain(
                    SaveAggregateServiceBuilder::create(
                        $aggregateClassDefinition,
                        $methodName,
                        $interfaceToCallRegistry,
                        $baseEventSourcingConfiguration
                    )
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chain(PublishAggregateEventsServiceBuilder::create($aggregateClassDefinition, $methodName, $interfaceToCallRegistry))
        );
    }
}
