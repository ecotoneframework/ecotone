<?php

namespace Ecotone\Modelling\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
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
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateMessageHandlerBuilder;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\Repository;
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
    const INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL = "cqrs.execute_message";
    const CQRS_MODULE                                          = "cqrsModule";
    const CQRS_MESSAGE_ROUTER_ENDPOINT_ID                      = "cqrsMessageRouter";

    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateCommandHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $serviceCommandHandlersRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateQueryHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $serviceQueryHandlerRegistrations;
    /**
     * @var array|AnnotationRegistration[]
     */
    private $aggregateEventHandlers;
    /**
     * @var array|AnnotationRegistration[]
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
     * @param AnnotationRegistration[]            $aggregateCommandHandlerRegistrations
     * @param AnnotationRegistration[]            $serviceCommandHandlersRegistrations
     * @param AnnotationRegistration[]            $aggregateQueryHandlerRegistrations
     * @param AnnotationRegistration[]            $serviceQueryHandlerRegistrations
     * @param AnnotationRegistration[]            $aggregateEventHandlers
     * @param AnnotationRegistration[]            $serviceEventHandlers
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
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        $aggregateRepositoryClasses = $annotationRegistrationService->getAllClassesWithAnnotation(Repository::class);

        $aggregateRepositoryReferenceNames = [];
        foreach ($aggregateRepositoryClasses as $aggregateRepositoryClass) {
            /** @var Repository $aggregateRepositoryAnnotation */
            $aggregateRepositoryAnnotation = $annotationRegistrationService->getAnnotationForClass($aggregateRepositoryClass, Repository::class);

            $aggregateRepositoryReferenceNames[] = $aggregateRepositoryAnnotation->referenceName ? $aggregateRepositoryAnnotation->referenceName : $aggregateRepositoryClass;
        }

        return new self(
            ParameterConverterAnnotationFactory::create(),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, EventHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EventHandler::class),
            $aggregateRepositoryReferenceNames
        );
    }

    /***
     * @param AnnotationRegistration $registration
     *
     * @return string
     */
    public static function getMessageChannelForEventHandler(AnnotationRegistration $registration): string
    {
        /** @var CommandHandler|QueryHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();

        return $methodAnnotation->endpointId . ".target";
    }

    /**
     * @param AnnotationRegistration $registration
     *
     * @return string|null
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function getMessagePayloadTypeFor(AnnotationRegistration $registration): string
    {
        $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());

        if ($registration->getAnnotationForMethod()->ignorePayload || $interfaceToCall->hasNoParameters()) {
            return TypeDescriptor::ARRAY;
        }

        $firstParameterType = $interfaceToCall->getFirstParameter()->getTypeDescriptor();

        if ($firstParameterType->isClassOrInterface() && !$firstParameterType->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $firstParameterType;
        }

        return TypeDescriptor::ARRAY;
    }

    public static function getHandlerChannel(AnnotationRegistration $registration): string
    {
        /** @var EndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        return $annotationForMethod->endpointId . ".target";
    }

    public static function getPayloadClassIfAny(AnnotationRegistration $registration): ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessagePayloadTypeFor($registration));
        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type;
        }

        return null;
    }

    public static function getNamedMessageChannelFor(AnnotationRegistration $registration): ?string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            return $registration->getAnnotationForMethod()->listenTo;
        }

        return $annotationForMethod->inputChannelName ?? null;
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
//            /** @var CommandHandler $annotationForMethod */
//            $annotationForMethod = $registration->getAnnotationForMethod();
//
//            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, self::getHandlerChannel($registration), $annotationForMethod->dropMessageOnNotFound, $annotationForMethod->endpointId);
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getHandlerChannel($registration)][] = $registration;
        }

        foreach ($this->aggregateEventHandlers as $registration) {
//            /** @var EventHandler $annotationForMethod */
//            $annotationForMethod = $registration->getAnnotationForMethod();
//
//            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, self::getHandlerChannel($registration), $annotationForMethod->dropMessageOnNotFound, $annotationForMethod->endpointId);
            $aggregateCommandOrEventHandlers[$registration->getClassName()][self::getHandlerChannel($registration)][] = $registration;
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
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();

            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $annotationForMethod->endpointId));
        }
        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $registration->getAnnotationForMethod()->endpointId));
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $registration->getAnnotationForMethod()->endpointId));
        }
    }

    /**
     * @var AnnotationRegistration[] $registrations
     */
    private function registerAggregateCommandHandler(Configuration $configuration, array $aggregateRepositoryReferenceNames, array $registrations, string $inputChannelName): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $registration = $registrations[0];

        $aggregateClassDefinition = ClassDefinition::createFor(TypeDescriptor::create($registration->getClassName()));
        $handledPayloadType       = self::getPayloadClassIfAny($registration);
        $handledPayloadType       = $handledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($handledPayloadType)) : null;

        if (count($registrations) > 2) {
            throw new \InvalidArgumentException("Can't handle");
        }

        $hasFactoryAndActionRedirect = count($registrations) === 2;
        if ($hasFactoryAndActionRedirect) {
            $configuration->registerMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($inputChannelName)
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, false)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    )
                    ->withOutputMessageHandler(RouterBuilder::createHeaderValueRouter(AggregateMessage::AGGREGATE_OBJECT_EXISTS, [true => "action." . $inputChannelName, false => "factory." . $inputChannelName]))
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
            $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignorePayload);
            $connectionChannel = $hasFactoryAndActionRedirect
                                    ? ($isFactoryMethod ? "factory." : "action.") . $inputChannelName
                                    : $inputChannelName;

            $chainHandler = ChainMessageHandlerBuilder::create()
                                ->withEndpointId($endpointId)
                                ->withInputChannelName($connectionChannel);

            if (!$isFactoryMethod) {
                $chainHandler = $chainHandler
                    ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, $annotation->identifierMetadataMapping, $handledPayloadType))
                    ->chain(
                        LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, $dropMessageOnNotFound)
                            ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    );
            }

            $chainHandler = $chainHandler->chain(
                AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith($aggregateClassDefinition, $registration->getMethodName())
                    ->withOutputMessageChannel($annotation->outputChannelName)
                    ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                    ->withMethodParameterConverters($parameterConverters)
                    ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
            );

            $configuration->registerMessageHandler($chainHandler);
        }

//        @TODO next handler for outputchannel, so after works after handler is done, not after output is done
//        write a test for it
    }

    private function registerAggregateQueryHandler(AnnotationRegistration $registration, ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory, Configuration $configuration): void
    {
        /** @var QueryHandler $annotation */
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignorePayload);

        $inputChannelName         = self::getHandlerChannel($registration);
        $aggregateClassDefinition = ClassDefinition::createFor(TypeDescriptor::create($registration->getClassName()));
        $handledPayloadType       = self::getPayloadClassIfAny($registration);
        $handledPayloadType       = $handledPayloadType ? ClassDefinition::createFor(TypeDescriptor::create($handledPayloadType)) : null;

        $connectionChannel = Uuid::uuid4()->toString();
        $configuration->registerMessageHandler(
            ChainMessageHandlerBuilder::create()
                ->withEndpointId($registration->getAnnotationForMethod()->endpointId)
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($connectionChannel)
                ->chain(AggregateIdentifierRetrevingServiceBuilder::createWith($aggregateClassDefinition, [], $handledPayloadType))
                ->chain(
                    LoadAggregateServiceBuilder::create($aggregateClassDefinition, $registration->getMethodName(), $handledPayloadType, false)
                        ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                )
        );

        $configuration->registerMessageHandler(
            AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith($aggregateClassDefinition, $registration->getMethodName())
                ->withInputChannelName($connectionChannel)
                ->withOutputMessageChannel($registration->getAnnotationForMethod()->outputChannelName)
                ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
        );
    }

    private function createServiceActivator(string $inputChannelName, AnnotationRegistration $registration, string $endpointId): ServiceActivatorBuilder
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $annotation                          = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignorePayload);

        return ServiceActivatorBuilder::create($registration->getReferenceName(), $registration->getMethodName())
            ->withInputChannelName($inputChannelName)
            ->withOutputMessageChannel($annotation->outputChannelName)
            ->withEndpointId($endpointId)
            ->withMethodParameterConverters($parameterConverters)
            ->withRequiredInterceptorNames($annotation->requiredInterceptorNames);
    }
}