<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
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
use Ecotone\Messaging\Handler\InterfaceToCall;
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
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\CallAggregateServiceBuilder;
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
        array $aggregateRepositoryReferenceNames
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
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService): static
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
            $aggregateRepositoryReferenceNames
        );
    }

    public static function getMessagePayloadTypeFor(AnnotatedFinding $registration): string
    {
        $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());

        if ($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)) || $interfaceToCall->hasNoParameters()) {
            return TypeDescriptor::ARRAY;
        }

        $firstParameterType = $interfaceToCall->getFirstParameter()->getTypeDescriptor();

        if ($firstParameterType->isClassOrInterface() && !$firstParameterType->isClassOfType(TypeDescriptor::create(Message::class))) {
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

    public static function getPayloadClassIfAny(AnnotatedFinding $registration): ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration));
        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type->toString();
        }

        return null;
    }

    public static function getEventPayloadClasses(AnnotatedFinding $registration): array
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration));
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

    public static function getNamedMessageChannelForEventHandler(AnnotatedFinding $registration): string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $inputChannelName = null;
        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $annotationForMethod->getListenTo();
        }

        if (!$inputChannelName) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            if ($interfaceToCall->hasNoParameters()) {
                throw ConfigurationException::create("Missing command class or listen routing for {$registration}.");
            }
            $inputChannelName = $interfaceToCall->getFirstParameterTypeHint();
        }

        return $inputChannelName;
    }

    public static function getNamedMessageChannelFor(AnnotatedFinding $registration): string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $annotationForMethod->getListenTo();
        } else {
            $inputChannelName = $annotationForMethod->getInputChannelName();
        }

        if (!$inputChannelName) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
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
        return $extensionObject instanceof RepositoryBuilder;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $configuration->requireReferences($this->aggregateRepositoryReferenceNames);
        foreach ($moduleExtensions as $aggregateRepositoryBuilder) {
            $referenceId = Uuid::uuid4()->toString();
            $moduleReferenceSearchService->store($referenceId, $aggregateRepositoryBuilder);
            $this->aggregateRepositoryReferenceNames[$referenceId] = $referenceId;
        }

        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlers as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getNamedMessageChannelFor($registration)][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getNamedMessageChannelForEventHandler($registration)][] = $registration;
        }

        foreach ($aggregateCommandOrEventHandlers as $channelNameRegistrations) {
            foreach ($channelNameRegistrations as $channelName => $registrations) {
                $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registrations, $channelName);
            }
        }

        foreach ($this->aggregateQueryHandlers as $registration) {
            $this->registerAggregateQueryHandler($registration, $parameterConverterAnnotationFactory, $configuration);
        }

        foreach ($this->serviceCommandHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration), $configuration, $registration);
        }
        foreach ($this->serviceQueryHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelFor($registration), $configuration, $registration);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $this->registerServiceHandler(self::getNamedMessageChannelForEventHandler($registration), $configuration, $registration);
        }
    }

    /**
     * @var AnnotatedDefinition[] $registrations
     */
    private function registerAggregateCommandHandler(Configuration $configuration, array $aggregateRepositoryReferenceNames, array $registrations, string $inputChannelName): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $registration = $registrations[0];

        $aggregateClassDefinition = ClassDefinition::createFor(TypeDescriptor::create($registration->getClassName()));
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
                Assert::null($factoryChannel, "Trying to register factory method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelName}");
                $factoryChannel                   = $channel;
                $factoryHandledPayloadType        = self::getPayloadClassIfAny($registration);
                $factoryHandledPayloadType        = $factoryHandledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($factoryHandledPayloadType)) : null;
                $factoryIdentifierMetadataMapping = $registration->getAnnotationForMethod()->identifierMetadataMapping;
            } else {
                Assert::null($actionChannel, "Trying to register action method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelName}");
                $actionChannel = $channel;
            }
        }

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $hasFactoryAndActionRedirect = count($registrations) === 2;
        if ($hasFactoryAndActionRedirect) {
            $inputChannelNameRouter = $inputChannelName . ".route";
            $configuration->registerMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($inputChannelName)
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryHandledPayloadType))
                    ->chainInterceptedHandler(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound())
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->withOutputMessageChannel($inputChannelNameRouter)
            );
            $configuration->registerMessageHandler(
                RouterBuilder::createHeaderMappingRouter(AggregateMessage::AGGREGATE_OBJECT_EXISTS, [true => $actionChannel, false => $factoryChannel])
                    ->withInputChannelName($inputChannelNameRouter)
            );
        }

        foreach ($registrations as $registration) {
            /** @var CommandHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $endpointId            = $annotation->getEndpointId();
            $dropMessageOnNotFound = $annotation->isDropMessageOnNotFound();

            $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $isFactoryMethod       = $relatedClassInterface->isStaticallyCalled();
            $parameterConverters   = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
            $connectionChannel     = $hasFactoryAndActionRedirect
                ? ($isFactoryMethod ? $factoryChannel : $actionChannel)
                : $inputChannelName;

            $saveChannel  = $connectionChannel . "save";
            $chainHandler = ChainMessageHandlerBuilder::create()
                ->withEndpointId($endpointId)
                ->withEndpointAnnotations([$annotation])
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($saveChannel);

            if (!$isFactoryMethod) {
                $handledPayloadType = self::getPayloadClassIfAny($registration);
                $handledPayloadType = $handledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($handledPayloadType)) : null;
                $chainHandler       = $chainHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->getIdentifierMetadataMapping(), $handledPayloadType))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound ? LoadAggregateMode::createDropMessageOnNotFound() : LoadAggregateMode::createThrowOnNotFound())
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    );
            }

            $chainHandler = $chainHandler
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), true)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                        ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
                );

            $configuration->registerMessageHandler($chainHandler);
            $configuration->registerMessageHandler(
                SaveAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName())
                    ->withInputChannelName($saveChannel)
                    ->withOutputMessageChannel($annotation->getOutputChannelName())
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
            );
        }
    }

    private function registerAggregateQueryHandler(AnnotatedFinding $registration, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory, Configuration $configuration): void
    {
        /** @var QueryHandler $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        $relatedClassInterface    = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverters      = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface, (bool)$relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
        $endpointChannelName      = self::getHandlerChannel($registration);
        $aggregateClassDefinition = ClassDefinition::createFor(TypeDescriptor::create($registration->getClassName()));
        $handledPayloadType       = self::getPayloadClassIfAny($registration);
        $handledPayloadType       = $handledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($handledPayloadType)) : null;


        $inputChannelName = self::getNamedMessageChannelFor($registration);
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
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], $handledPayloadType))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound())
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false)
                        ->withEndpointId($annotationForMethod->getEndpointId())
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withRequiredInterceptorNames($annotationForMethod->getRequiredInterceptorNames())
                )
        );
    }

    private function registerServiceHandler(string $inputChannelName, Configuration $configuration, AnnotatedFinding $registration): void
    {
        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation                    = $registration->getAnnotationForMethod();
        $endpointInputChannel                = self::getHandlerChannel($registration);
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
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