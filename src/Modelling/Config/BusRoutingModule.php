<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NotUniqueHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use Ecotone\Modelling\QueryBus;
use ReflectionMethod;

#[ModuleAnnotation]
class BusRoutingModule implements AnnotationModule
{
    const MODULE_NAME = self::class;

    private BusRouterBuilder $commandBusByObject;
    private BusRouterBuilder $queryBusByObject;
    private BusRouterBuilder $eventBusByObject;
    private BusRouterBuilder $commandBusByName;
    private BusRouterBuilder $queryBusByName;
    private BusRouterBuilder $eventBusByName;
    private MessageHeadersPropagator $messageHeadersPropagator;

    public function __construct(MessageHeadersPropagator $messageHeadersPropagator, BusRouterBuilder $commandBusByObject, BusRouterBuilder $commandBusByName, BusRouterBuilder $queryBusByObject, BusRouterBuilder $queryBusByName, BusRouterBuilder $eventBusByObject, BusRouterBuilder $eventBusByName)
    {
        $this->commandBusByObject       = $commandBusByObject;
        $this->queryBusByObject         = $queryBusByObject;
        $this->eventBusByObject         = $eventBusByObject;
        $this->commandBusByName         = $commandBusByName;
        $this->queryBusByName           = $queryBusByName;
        $this->eventBusByName           = $eventBusByName;
        $this->messageHeadersPropagator = $messageHeadersPropagator;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $messageHeadersPropagator = new MessageHeadersPropagator();

        $uniqueObjectChannels = [];
        $uniqueNameChannels = [];
        return new self(
            $messageHeadersPropagator,
            BusRouterBuilder::createCommandBusByObject($messageHeadersPropagator, self::getCommandBusByObjectMapping($annotationRegistrationService, $interfaceToCallRegistry, false, $uniqueObjectChannels)),
            BusRouterBuilder::createCommandBusByName($messageHeadersPropagator, self::getCommandBusByNamesMapping($annotationRegistrationService, $interfaceToCallRegistry, false, $uniqueNameChannels)),
            BusRouterBuilder::createQueryBusByObject($messageHeadersPropagator, self::getQueryBusByObjectsMapping($annotationRegistrationService, $interfaceToCallRegistry, $uniqueObjectChannels)),
            BusRouterBuilder::createQueryBusByName($messageHeadersPropagator, self::getQueryBusByNamesMapping($annotationRegistrationService, $interfaceToCallRegistry, $uniqueNameChannels)),
            BusRouterBuilder::createEventBusByObject($messageHeadersPropagator, self::getEventBusByObjectsMapping($annotationRegistrationService, $interfaceToCallRegistry, false)),
            BusRouterBuilder::createEventBusByName($messageHeadersPropagator, self::getEventBusByNamesMapping($annotationRegistrationService, $interfaceToCallRegistry, false))
        );
    }

    public static function getCommandBusByObjectMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, bool $hasToBeDistributed, array &$uniqueChannels = []): array
    {
        $objectCommandHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, CommandHandler::class) as $registration) {
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }
            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) && !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
            if ($classChannel) {
                $objectCommandHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
                $objectCommandHandlers[$classChannel]   = array_unique($objectCommandHandlers[$classChannel]);
                $uniqueChannels[$classChannel][]        = $registration;
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(CommandHandler::class) as $registration) {
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }
            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) && !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
            if ($classChannel) {
                $objectCommandHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
                $objectCommandHandlers[$classChannel]   = array_unique($objectCommandHandlers[$classChannel]);
                $uniqueChannels[$classChannel][]        = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $objectCommandHandlers;
    }

    public static function getCommandBusByNamesMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, bool $hasToBeDistributed, array &$uniqueChannels = []): array
    {
        $namedCommandHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, CommandHandler::class) as $registration) {
            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) && !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            if ($namedChannel) {
                $namedCommandHandlers[$namedChannel][] = $namedChannel;
                $namedCommandHandlers[$namedChannel]   = array_unique($namedCommandHandlers[$namedChannel]);
                $uniqueChannels[$namedChannel][]       = $registration;
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(CommandHandler::class) as $registration) {
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }
            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) && !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            if ($namedChannel) {
                $namedCommandHandlers[$namedChannel][] = $namedChannel;
                $namedCommandHandlers[$namedChannel]   = array_unique($namedCommandHandlers[$namedChannel]);
                $uniqueChannels[$namedChannel][]       = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $namedCommandHandlers;
    }

    public static function getQueryBusByObjectsMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, array &$uniqueChannels = []): array
    {
        $objectQueryHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, QueryHandler::class) as $registration) {
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
            if ($classChannel) {
                $objectQueryHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
                $objectQueryHandlers[$classChannel]   = array_unique($objectQueryHandlers[$classChannel]);
                $uniqueChannels[$classChannel][]      = $registration;
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(QueryHandler::class) as $registration) {
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration, $interfaceToCallRegistry);
            if ($classChannel) {
                $objectQueryHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
                $objectQueryHandlers[$classChannel]   = array_unique($objectQueryHandlers[$classChannel]);
                $uniqueChannels[$classChannel][]      = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $objectQueryHandlers;
    }

    public static function getQueryBusByNamesMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, array &$uniqueChannels = []): array
    {
        $namedQueryHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, QueryHandler::class) as $registration) {
            $namedChannel                        = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            $namedQueryHandlers[$namedChannel][] = $namedChannel;
            $namedQueryHandlers[$namedChannel]   = array_unique($namedQueryHandlers[$namedChannel]);
            $uniqueChannels[$namedChannel][]     = $registration;
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(QueryHandler::class) as $registration) {
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }

            $namedChannel                        = ModellingHandlerModule::getNamedMessageChannelFor($registration, $interfaceToCallRegistry);
            $namedQueryHandlers[$namedChannel][] = $namedChannel;
            $namedQueryHandlers[$namedChannel]   = array_unique($namedQueryHandlers[$namedChannel]);
            $uniqueChannels[$namedChannel][]     = $registration;
        }

        self::verifyUniqueness($uniqueChannels);

        return $namedQueryHandlers;
    }

    public static function getEventBusByObjectsMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, bool $hasToBeDistributed): array
    {
        $objectEventHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, EventHandler::class) as $registration) {
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }
            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) || !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $classChannels           = ModellingHandlerModule::getEventPayloadClasses($registration, $interfaceToCallRegistry);
            $namedMessageChannelFor = ModellingHandlerModule::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);

            foreach ($classChannels as $classChannel) {
                $objectEventHandlers[$classChannel][] = $namedMessageChannelFor;
                $objectEventHandlers[$classChannel]   = array_unique($objectEventHandlers[$classChannel]);
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(EventHandler::class) as $registration) {
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            if ($hasToBeDistributed && (!$registration->hasMethodAnnotation(Distributed::class) || !$registration->hasClassAnnotation(Distributed::class))) {
                continue;
            }

            $classChannels           = ModellingHandlerModule::getEventPayloadClasses($registration, $interfaceToCallRegistry);
            $namedMessageChannelFor = ModellingHandlerModule::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);
            foreach ($classChannels as $classChannel) {
                if (!EventBusRouter::isRegexBasedRoute($namedMessageChannelFor)) {
                    $objectEventHandlers[$classChannel][] = $namedMessageChannelFor;
                    $objectEventHandlers[$classChannel]   = array_unique($objectEventHandlers[$classChannel]);
                }
            }
        }

        return $objectEventHandlers;
    }

    public static function getEventBusByNamesMapping(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry, bool $hasToBeDistributed): array
    {
        $namedEventHandlers = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(EventHandler::class) as $registration) {
            /** @var EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();
            if ($registration->hasClassAnnotation(Aggregate::class)) {
                continue;
            }
            if ($hasToBeDistributed && !$registration->hasMethodAnnotation(Distributed::class)) {
                continue;
            }

            $chanelName = ModellingHandlerModule::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);

            if ($annotation->getListenTo()) {
                $namedEventHandlers[$chanelName][] = $chanelName;
                $namedEventHandlers[$chanelName]   = array_unique($namedEventHandlers[$chanelName]);
            }else {
                $type = TypeDescriptor::create($chanelName);
                if ($type->isUnionType()) {
                    foreach ($type->getUnionTypes() as $type) {
                        $namedEventHandlers[$type->toString()][] = $chanelName;
                        $namedEventHandlers[$type->toString()]   = array_unique($namedEventHandlers[$type->toString()]);
                    }
                }else {
                    $namedEventHandlers[$chanelName][] = $chanelName;
                    $namedEventHandlers[$chanelName]   = array_unique($namedEventHandlers[$chanelName]);
                }
            }
        }
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, EventHandler::class) as $registration) {
            $channelName = ModellingHandlerModule::getNamedMessageChannelForEventHandler($registration, $interfaceToCallRegistry);
            if (EventBusRouter::isRegexBasedRoute($channelName)) {
                throw ConfigurationException::create("Can not registered regex listen to channel for aggregates in {$registration}");
            }
            if ($hasToBeDistributed && !$registration->hasMethodAnnotation(Distributed::class)) {
                continue;
            }

            $namedEventHandlers[$channelName][] = $channelName;
            $namedEventHandlers[$channelName]   = array_unique($namedEventHandlers[$channelName]);
        }

        return $namedEventHandlers;
    }

    private static function isForTheSameAggregate(array $aggregateMethodUsage, $uniqueChannelName, string $oppositeMethodType, AnnotatedFinding $registration): bool
    {
        return !isset($aggregateMethodUsage[$uniqueChannelName][$oppositeMethodType])
            || $aggregateMethodUsage[$uniqueChannelName][$oppositeMethodType]->getClassName() === $registration->getClassName();
    }

    /**
     * @param AnnotatedDefinition[][] $uniqueChannels
     *
     * @throws MessagingException
     */
    private static function verifyUniqueness(array $uniqueChannels): void
    {
        $notUniqueHandlerAnnotation = TypeDescriptor::create(NotUniqueHandler::class);
        $aggregateAnnotation        = TypeDescriptor::create(Aggregate::class);
        foreach ($uniqueChannels as $uniqueChannelName => $registrations) {
            $combinedRegistrationNames = "";
            $registrationsToVerify     = [];
            $aggregateMethodUsage      = [];
            foreach ($registrations as $registration) {
                if ($registration->hasMethodAnnotation($notUniqueHandlerAnnotation)) {
                    continue;
                }

                if ($registration->hasClassAnnotation($aggregateAnnotation)) {
                    $isStatic           = (new ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic();
                    $methodType         = $isStatic ? "factory" : "action";
                    $oppositeMethodType = $isStatic ? "action" : "factory";
                    if (!isset($aggregateMethodUsage[$uniqueChannelName][$methodType])) {
                        $aggregateMethodUsage[$uniqueChannelName][$methodType] = $registration;
                        if (self::isForTheSameAggregate($aggregateMethodUsage, $uniqueChannelName, $oppositeMethodType, $registration)) {
                            continue;
                        }
                    }

                    $registrationsToVerify[] = $aggregateMethodUsage[$uniqueChannelName][$methodType];
                }

                $registrationsToVerify[] = $registration;
            }

            if (count($registrationsToVerify) <= 1) {
                continue;
            }

            foreach ($registrationsToVerify as $registration) {
                $combinedRegistrationNames .= " {$registration->getClassName()}:{$registration->getMethodName()}";
            }

            throw ConfigurationException::create("Channel name `{$uniqueChannelName}` should be unique, but is used in multiple handlers:{$combinedRegistrationNames}");
        }
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    MessageHeadersPropagator::class,
                    $interfaceToCallRegistry->getFor(MessageHeadersPropagator::class, "propagateHeaders"),
                    TransformerBuilder::createWithDirectObject($this->messageHeadersPropagator, "propagateHeaders")
                        ->withMethodParameterConverters(
                            [
                                AllHeadersBuilder::createWith("headers")
                            ]
                        ),
                    Precedence::ENDPOINT_HEADERS_PRECEDENCE - 2,
                    CommandBus::class . "||" . EventBus::class . "||" . QueryBus::class . "||" . AsynchronousRunningEndpoint::class
                )
            )
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    $this->messageHeadersPropagator,
                    "storeHeaders",
                    Precedence::ENDPOINT_HEADERS_PRECEDENCE - 1,
                    CommandBus::class . "||" . EventBus::class . "||" . QueryBus::class . "||" . AsynchronousRunningEndpoint::class
                )
            )
            ->registerMessageHandler($this->commandBusByObject)
            ->registerMessageHandler($this->commandBusByName)
            ->registerMessageHandler($this->queryBusByObject)
            ->registerMessageHandler($this->queryBusByName)
            ->registerMessageHandler($this->eventBusByObject)
            ->registerMessageHandler($this->eventBusByName);
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}