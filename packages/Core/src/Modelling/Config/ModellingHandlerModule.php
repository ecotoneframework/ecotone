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
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
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
use Ecotone\Modelling\CallAggregateServiceBuilder;
use Ecotone\Modelling\FetchAggregate;
use Ecotone\Modelling\LoadAggregateMode;
use Ecotone\Modelling\LoadAggregateServiceBuilder;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;

#[ModuleAnnotation]
class ModellingHandlerModule implements AnnotationModule
{
    const CQRS_MODULE = "cqrsModule";

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
    )
    {
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
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return $annotatedFinding->hasClassAnnotation(Aggregate::class);
            }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return !$annotatedFinding->hasClassAnnotation(Aggregate::class);
            }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return $annotatedFinding->hasClassAnnotation(Aggregate::class);
            }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return !$annotatedFinding->hasClassAnnotation(Aggregate::class);
            }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return $annotatedFinding->hasClassAnnotation(Aggregate::class);
            }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class), function (AnnotatedFinding $annotatedFinding) {
                return !$annotatedFinding->hasClassAnnotation(Aggregate::class);
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

        if ($firstParameterType->isClassOrInterface() && !$firstParameterType->isClassOfType(TypeDescriptor::create(Message::class))) {
            $reflectionParameter = new \ReflectionParameter([$registration->getClassName(), $registration->getMethodName()], 0);

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

        return $annotationForMethod->getEndpointId() . ".target";
    }

    public static function getPayloadClassIfAny(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration, $interfaceToCallRegistry));

        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type->toString();
        }

        return null;
    }

    public static function getEventPayloadClasses(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration, $interfaceToCallRegistry));
        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
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

        if (!$inputChannelName) {
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

        if (!$inputChannelName) {
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
    public function getRelatedReferences(): array
    {
        return [];
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

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $configuration->requireReferences($this->aggregateRepositoryReferenceNames);
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
            Assert::isTrue($interface->getReturnType()->isClassNotInterface() || $interface->getReturnType()->isVoid(), "Repository should have return type of Aggregate class or void if is save method: " . $repositoryGateway);

            $inputChannelName = $repositoryGateway->getClassName() . $repositoryGateway->getMethodName() . ($interface->getReturnType()->isVoid() ? ".save" : ".load" . ($interface->canItReturnNull() ? ".nullable" : ""));

            $chainMessageHandlerBuilder = ChainMessageHandlerBuilder::create()
                                            ->withInputChannelName($inputChannelName);
            if ($interface->getReturnType()->isVoid()) {
                Assert::isTrue($interface->hasFirstParameter(), "Saving repository should have at least one parameter for aggregate: " . $repositoryGateway);

                if ($interface->hasMethodAnnotation(TypeDescriptor::create(RelatedAggregate::class))) {
                    Assert::isTrue($interface->hasSecondParameter(), "Saving repository should have first parameter as identifier and second as array of events in: " . $repositoryGateway);

                    /** @var RelatedAggregate $relatedAggregate */
                    $relatedAggregate = $interface->getMethodAnnotation(TypeDescriptor::create(RelatedAggregate::class));

                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($relatedAggregate->getClassName()));

                    $gatewayParameterConverters = [
                        GatewayHeaderBuilder::create($interface->getFirstParameterName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER),
                        GatewayHeaderBuilder::create($interface->getSecondParameter()->getName(), AggregateMessage::TARGET_VERSION),
                        GatewayPayloadBuilder::create($interface->getThirdParameter()),
                        GatewayHeaderValueBuilder::create(AggregateMessage::AGGREGATE_OBJECT, $aggregateClassDefinition->getClassType()->toString())
                    ];

                    $chainMessageHandlerBuilder = $chainMessageHandlerBuilder
                        ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], null, $interfaceToCallRegistry));
                }else {
                    Assert::isTrue($interface->getFirstParameter()->getTypeDescriptor()->isClassNotInterface(), "Saving repository should type hint for Aggregate or if is Event Sourcing make use of RelatedAggregate attribute in: " . $repositoryGateway);
                    $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor($interface->getFirstParameter()->getTypeDescriptor());

                    $gatewayParameterConverters = [GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::AGGREGATE_OBJECT)];
                }


                /** @TODO do not require method name in save service */
                $methodName = $aggregateClassDefinition->getPublicMethodNames() ? $aggregateClassDefinition->getPublicMethodNames()[0] : "__construct";
                $configuration->registerMessageHandler(
                    $chainMessageHandlerBuilder
                        ->chain(SaveAggregateServiceBuilder::create($aggregateClassDefinition, $methodName, $interfaceToCallRegistry, $baseEventSourcingConfiguration->getSnapshotTriggerThreshold(), $baseEventSourcingConfiguration->getSnapshotsAggregateClasses(), $baseEventSourcingConfiguration->getDocumentStoreReference())
                            ->withInputChannelName($inputChannelName)
                            ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames))
                );
            }else {
                Assert::isTrue($interface->hasFirstParameter(), "Fetchting repository should have at least one parameter for identifiers: " . $repositoryGateway);
                $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor($interface->getReturnType());

                $configuration->registerMessageHandler($chainMessageHandlerBuilder
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], null, $interfaceToCallRegistry))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $interface->getMethodName(), null, $interface->canItReturnNull() ? LoadAggregateMode::createContinueOnNotFound() : LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                    )
                    ->chain(
                        ServiceActivatorBuilder::createWithDirectReference(new FetchAggregate(), "fetch")
                            ->withMethodParameterConverters([
                                HeaderBuilder::createOptional("aggregate", AggregateMessage::AGGREGATE_OBJECT)
                            ])
                    )
                );

                $gatewayParameterConverters = [GatewayHeaderBuilder::create($interface->getFirstParameter()->getName(), AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)];
            }

            $configuration->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getClassName(),
                    $repositoryGateway->getMethodName(),
                    $inputChannelName
                )->withParameterConverters($gatewayParameterConverters)
            );
        }

        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlers as $registration) {
            $channelName = self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $channelName = self::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);
            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($channelName));
            $aggregateCommandOrEventHandlers[$registration->getClassName()][$channelName][] = $registration;
        }

        foreach ($aggregateCommandOrEventHandlers as $channelNameRegistrations) {
            foreach ($channelNameRegistrations as $channelName => $registrations) {
                $this->registerAggregateCommandHandler($configuration, $interfaceToCallRegistry, $this->aggregateRepositoryReferenceNames, $registrations, $channelName, $baseEventSourcingConfiguration);
            }
        }

        foreach ($this->aggregateQueryHandlers as $registration) {
            $this->registerAggregateQueryHandler($registration, $interfaceToCallRegistry, $parameterConverterAnnotationFactory, $configuration);
        }

        foreach ($this->serviceCommandHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry), $configuration, $registration, $interfaceToCallRegistry);
        }
        foreach ($this->serviceQueryHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration, $interfaceToCallRegistry), $configuration, $registration, $interfaceToCallRegistry);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry), $configuration, $registration, $interfaceToCallRegistry);
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
        if (count($registrations) > 2) {
            throw new InvalidArgumentException("Can't handle");
        }

        $actionChannel                    = null;
        $factoryChannel                   = null;
        $factoryHandledPayloadType        = null;
        $factoryIdentifierMetadataMapping = [];
        foreach ($registrations as $registration) {
            $channel = self::getHandlerChannel($registration);
            if ((new ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic()) {
                Assert::null($factoryChannel, "Trying to register factory method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$messageChannelName}");
                $factoryChannel                   = $channel;
                $factoryHandledPayloadType        = self::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
                $factoryHandledPayloadType        = $factoryHandledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($factoryHandledPayloadType)) : null;
                $factoryIdentifierMetadataMapping = $registration->getAnnotationForMethod()->identifierMetadataMapping;
            } else {
                Assert::null($actionChannel, "Trying to register action method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$messageChannelName}");
                $actionChannel = $channel;
            }
        }

        $hasFactoryAndActionRedirect = count($registrations) === 2;
        if ($hasFactoryAndActionRedirect) {
            $messageChannelNameRouter = Uuid::uuid4()->toString();
            $configuration->registerMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($messageChannelName)
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryHandledPayloadType, $interfaceToCallRegistry))
                    ->chainInterceptedHandler(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->withOutputMessageChannel($messageChannelNameRouter)
            );

            $configuration->registerMessageHandler(
                RouterBuilder::createHeaderMappingRouter(AggregateMessage::AGGREGATE_OBJECT_EXISTS, [true => $actionChannel, false => $factoryChannel])
                    ->withInputChannelName($messageChannelNameRouter)
            );
        }

        foreach ($registrations as $registration) {
            /** @var CommandHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $endpointId            = $annotation->getEndpointId();
            $dropMessageOnNotFound = $annotation->isDropMessageOnNotFound();

            $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $isFactoryMethod       = $relatedClassInterface->isStaticallyCalled();
            $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
            $connectionChannel     = $hasFactoryAndActionRedirect
                ? ($isFactoryMethod ? $factoryChannel : $actionChannel)
                : self::getHandlerChannel($registration);
            if (!$hasFactoryAndActionRedirect) {
                $configuration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withInputChannelName($messageChannelName)
                        ->withOutputMessageChannel($connectionChannel)
                );
            }

            $saveChannel  = $connectionChannel . "save";
            $chainHandler = ChainMessageHandlerBuilder::create()
                ->withEndpointId($endpointId)
                ->withEndpointAnnotations([$annotation])
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($saveChannel);

            if (!$isFactoryMethod) {
                $handledPayloadType = self::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
                $handledPayloadType = $handledPayloadType ? $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($handledPayloadType)) : null;
                $chainHandler       = $chainHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $handledPayloadType, $interfaceToCallRegistry))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound ? LoadAggregateMode::createDropMessageOnNotFound() : LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    );
            }

            $chainHandler = $chainHandler
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), true, $interfaceToCallRegistry)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                        ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
                );

            $configuration->registerMessageHandler($chainHandler);
            $configuration->registerMessageHandler(
                SaveAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $interfaceToCallRegistry, $baseEventSourcingConfiguration->getSnapshotTriggerThreshold(), $baseEventSourcingConfiguration->getSnapshotsAggregateClasses(), $baseEventSourcingConfiguration->getDocumentStoreReference())
                    ->withInputChannelName($saveChannel)
                    ->withOutputMessageChannel($annotation->getOutputChannelName())
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
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
                ->withEndpointAnnotations([$annotationForMethod])
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], $handledPayloadType, $interfaceToCallRegistry))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound(), $interfaceToCallRegistry)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false, $interfaceToCallRegistry)
                        ->withEndpointId($annotationForMethod->getEndpointId())
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withRequiredInterceptorNames($annotationForMethod->getRequiredInterceptorNames())
                )
        );
    }

    private function registerServiceHandler(string $inputChannelName, Configuration $configuration, AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation                    = $registration->getAnnotationForMethod();
        $endpointInputChannel                = self::getHandlerChannel($registration);
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $configuration->registerMessageHandler(
            BridgeBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($endpointInputChannel)
        );

        $handler = $registration->hasMethodAnnotation(ChangingHeaders::class)
            ? TransformerBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $registration->getMethodName())
            : ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $registration->getMethodName());

        $configuration->registerMessageHandler(
            $handler
                ->withInputChannelName($endpointInputChannel)
                ->withOutputMessageChannel($methodAnnotation->getOutputChannelName())
                ->withEndpointId($methodAnnotation->getEndpointId())
                ->withEndpointAnnotations([$methodAnnotation])
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($methodAnnotation->getRequiredInterceptorNames())
        );
    }
}