<?php

namespace Ecotone\Modelling\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
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
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Annotation\IgnorePayload;
use Ecotone\Modelling\CallAggregateServiceBuilder;
use Ecotone\Modelling\LoadAggregateMode;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\CallAggregateService;
use Ecotone\Modelling\LoadAggregateServiceBuilder;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ModellingHandlerModule implements AnnotationModule
{
    const CQRS_MODULE                                          = "cqrsModule";

    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;
    /**
     * @var AnnotatedDefinition[]
     */
    private $aggregateCommandHandlerRegistrations;
    /**
     * @var AnnotatedDefinition[]
     */
    private $serviceCommandHandlersRegistrations;
    /**
     * @var AnnotatedDefinition[]
     */
    private $aggregateQueryHandlerRegistrations;
    /**
     * @var AnnotatedDefinition[]
     */
    private $serviceQueryHandlerRegistrations;
    /**
     * @var array|AnnotatedDefinition[]
     */
    private $aggregateEventHandlers;
    /**
     * @var array|AnnotatedDefinition[]
     */
    private $serviceEventHandlers;
    /**
     * @var string[]
     */
    private $aggregateRepositoryReferenceNames;

    /**
     * CqrsMessagingModule constructor.
     *
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     * @param AnnotatedDefinition[]            $aggregateCommandHandlerRegistrations
     * @param AnnotatedDefinition[]            $serviceCommandHandlersRegistrations
     * @param AnnotatedDefinition[]            $aggregateQueryHandlerRegistrations
     * @param AnnotatedDefinition[]            $serviceQueryHandlerRegistrations
     * @param AnnotatedDefinition[]            $aggregateEventHandlers
     * @param AnnotatedDefinition[]            $serviceEventHandlers
     * @param array                               $aggregateRepositoryReferenceNames
     */
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
        $this->parameterConverterAnnotationFactory  = $parameterConverterAnnotationFactory;
        $this->aggregateCommandHandlerRegistrations = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlerRegistrations   = $aggregateQueryHandlerRegistrations;
        $this->serviceCommandHandlersRegistrations  = $serviceCommandHandlersRegistrations;
        $this->serviceQueryHandlerRegistrations     = $serviceQueryHandlerRegistrations;
        $this->aggregateEventHandlers               = $aggregateEventHandlers;
        $this->serviceEventHandlers                 = $serviceEventHandlers;
        $this->aggregateRepositoryReferenceNames    = $aggregateRepositoryReferenceNames;
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService): AnnotationModule
    {
        $aggregateRepositoryClasses = $annotationRegistrationService->findAnnotatedClasses(Repository::class);

        $aggregateRepositoryReferenceNames = [];
        foreach ($aggregateRepositoryClasses as $aggregateRepositoryClass) {
            /** @var Repository $aggregateRepositoryAnnotation */
            $aggregateRepositoryAnnotation = AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $aggregateRepositoryClass, Repository::class);

            $aggregateRepositoryReferenceNames[] = $aggregateRepositoryAnnotation->referenceName ? $aggregateRepositoryAnnotation->referenceName : $aggregateRepositoryClass;
        }

        return new self(
            ParameterConverterAnnotationFactory::create(),
            $annotationRegistrationService->findCombined(Aggregate::class, CommandHandler::class),
            $annotationRegistrationService->findCombined(MessageEndpoint::class, CommandHandler::class),
            $annotationRegistrationService->findCombined(Aggregate::class, QueryHandler::class),
            $annotationRegistrationService->findCombined(MessageEndpoint::class, QueryHandler::class),
            $annotationRegistrationService->findCombined(Aggregate::class, EventHandler::class),
            $annotationRegistrationService->findCombined(MessageEndpoint::class, EventHandler::class),
            $aggregateRepositoryReferenceNames
        );
    }

    public static function getMessagePayloadTypeFor(AnnotatedDefinition $registration): string
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

    public static function getHandlerChannel(AnnotatedDefinition $registration): string
    {
        /** @var EndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        return $annotationForMethod->endpointId . ".target";
    }

    public static function getPayloadClassIfAny(AnnotatedDefinition $registration): ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration));
        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type;
        }

        return null;
    }

    public static function hasMessageNameDefined(AnnotatedDefinition $registration) : bool
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $registration->getAnnotationForMethod()->listenTo;
        }else {
            $inputChannelName = $annotationForMethod->inputChannelName;
        }

        return $inputChannelName ? true : false;
    }

    public static function getNamedMessageChannelFor(AnnotatedDefinition $registration): string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $inputChannelName = $registration->getAnnotationForMethod()->listenTo;
        }else {
            $inputChannelName = $annotationForMethod->inputChannelName;
        }

        if (!$inputChannelName) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            if ($interfaceToCall->hasNoParameters()) {
                throw ConfigurationException::create("Missing command class or input channel for {$registration}.");
            }
            $inputChannelName = $interfaceToCall->getFirstParameterTypeHint();
        }

        return $inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::CQRS_MODULE;
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
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $configuration->requireReferences($this->aggregateRepositoryReferenceNames);

        $aggregateCommandOrEventHandlers = [];
        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getNamedMessageChannelFor($registration)][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getNamedMessageChannelFor($registration)][] = $registration;
        }

        foreach ($aggregateCommandOrEventHandlers as $className => $channelNameRegistrations) {
            foreach ($channelNameRegistrations as $channelName => $registrations) {
                $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registrations, $channelName);
            }
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            $this->registerAggregateQueryHandler($registration, $parameterConverterAnnotationFactory, $configuration);
        }

        foreach ($this->serviceCommandHandlersRegistrations as $registration) {
            $this->registerServiceHandler($configuration, $registration);
        }
        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $this->registerServiceHandler($configuration, $registration);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $this->registerServiceHandler($configuration, $registration);
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
            throw new \InvalidArgumentException("Can't handle");
        }

        $actionChannel = null;
        $factoryChannel = null;
        $factoryHandledPayloadType = null;
        $factoryIdentifierMetadataMapping = [];
        foreach ($registrations as $registration) {
            $channel = self::getHandlerChannel($registration);
            if ((new \ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic()) {
                Assert::null($factoryChannel, "Trying to register factory method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelName}");
                $factoryChannel = $channel;
                $factoryHandledPayloadType       = self::getPayloadClassIfAny($registration);
                $factoryHandledPayloadType       = $factoryHandledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($factoryHandledPayloadType)) : null;
                $factoryIdentifierMetadataMapping = $registration->getAnnotationForMethod()->identifierMetadataMapping;
            }else {
                Assert::null($actionChannel, "Trying to register action method for {$aggregateClassDefinition->getClassType()->toString()} twice under same channel {$inputChannelName}");
                $actionChannel = $channel;
            }
        }

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $hasFactoryAndActionRedirect = count($registrations) === 2;
        if ($hasFactoryAndActionRedirect) {
            $configuration->registerMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($inputChannelName)
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $factoryIdentifierMetadataMapping, $factoryHandledPayloadType))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $factoryHandledPayloadType, LoadAggregateMode::createContinueOnNotFound())
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->withOutputMessageHandler(RouterBuilder::createHeaderValueRouter(AggregateMessage::AGGREGATE_OBJECT_EXISTS, [true => $actionChannel, false => $factoryChannel]))
            );
        }

        foreach ($registrations as $registration) {
            /** @var CommandHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $endpointId = $annotation->endpointId;
            $dropMessageOnNotFound = $annotation->dropMessageOnNotFound;

            $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $isFactoryMethod = $relatedClassInterface->isStaticallyCalled();
            $parameterConverterAnnotations = $annotation->parameterConverters;
            $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
            $connectionChannel = $hasFactoryAndActionRedirect
                                    ? ($isFactoryMethod ? $factoryChannel : $actionChannel)
                                    : $inputChannelName;

            $saveChannel = $connectionChannel . "save";
            $chainHandler = ChainMessageHandlerBuilder::create()
                                ->withEndpointId($endpointId)
                                ->withInputChannelName($connectionChannel)
                                ->withOutputMessageChannel($saveChannel);

            if (!$isFactoryMethod) {
                $handledPayloadType       = self::getPayloadClassIfAny($registration);
                $handledPayloadType       = $handledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($handledPayloadType)) : null;
                $chainHandler = $chainHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->identifierMetadataMapping, $handledPayloadType))
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
                );

            $configuration->registerMessageHandler($chainHandler);
            $configuration->registerMessageHandler(
                SaveAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName())
                    ->withInputChannelName($saveChannel)
                    ->withOutputMessageChannel($annotation->outputChannelName)
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
            );
        }
    }

    private function registerAggregateQueryHandler(AnnotatedDefinition $registration, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory, Configuration $configuration): void
    {
        /** @var QueryHandler $annotation */
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
        $endpointChannelName         = self::getHandlerChannel($registration);
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
                ->withOutputMessageChannel($registration->getAnnotationForMethod()->outputChannelName)
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], $handledPayloadType))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, LoadAggregateMode::createThrowOnNotFound())
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
                ->chainInterceptedHandler(
                    CallAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), false)
                        ->withEndpointId($registration->getAnnotationForMethod()->endpointId)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                        ->withMethodParameterConverters($parameterConverters)
                        ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
                )
        );
    }

    private function registerServiceHandler(Configuration $configuration, AnnotatedDefinition $registration): void
    {
        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();
        $inputChannelName = self::getNamedMessageChannelFor($registration);
        $endpointInputChannel = self::getHandlerChannel($registration);
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $annotation                          = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $relatedClassInterface->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        $configuration->registerMessageHandler(
            BridgeBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($endpointInputChannel)
        );
        $configuration->registerMessageHandler(
            ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $registration->getMethodName())
                ->withInputChannelName($endpointInputChannel)
                ->withOutputMessageChannel($annotation->outputChannelName)
                ->withEndpointId($methodAnnotation->endpointId)
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
        );
    }
}