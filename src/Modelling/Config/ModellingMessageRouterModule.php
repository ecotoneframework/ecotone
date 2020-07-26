<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\NotUniqueHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AggregateMessageRouterModule
 * @package Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ModellingMessageRouterModule implements AnnotationModule
{
    const MODULE_NAME = self::class;

    /**
     * @var BusRouterBuilder
     */
    private $commandBusByObject;
    /**
     * @var BusRouterBuilder
     */
    private $queryBusByObject;
    /**
     * @var BusRouterBuilder
     */
    private $eventBusByObject;
    /**
     * @var BusRouterBuilder
     */
    private $commandBusByName;
    /**
     * @var BusRouterBuilder
     */
    private $queryBusByName;
    /**
     * @var BusRouterBuilder
     */
    private $eventBusByName;

    /**
     * AggregateMessageRouterModule constructor.
     *
     * @param BusRouterBuilder $commandBusByObject
     * @param BusRouterBuilder $commandBusByName
     * @param BusRouterBuilder $queryBusByObject
     * @param BusRouterBuilder $queryBusByName
     * @param BusRouterBuilder $eventBusByObject
     * @param BusRouterBuilder $eventBusByName
     */
    public function __construct(BusRouterBuilder $commandBusByObject, BusRouterBuilder $commandBusByName, BusRouterBuilder $queryBusByObject, BusRouterBuilder $queryBusByName, BusRouterBuilder $eventBusByObject, BusRouterBuilder $eventBusByName)
    {
        $this->commandBusByObject = $commandBusByObject;
        $this->queryBusByObject   = $queryBusByObject;
        $this->eventBusByObject   = $eventBusByObject;
        $this->commandBusByName = $commandBusByName;
        $this->queryBusByName = $queryBusByName;
        $this->eventBusByName = $eventBusByName;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService)
    {
        return new self(
            BusRouterBuilder::createCommandBusByObject(self::getCommandBusByObjectMapping($annotationRegistrationService)),
            BusRouterBuilder::createCommandBusByName(self::getCommandBusByNamesMapping($annotationRegistrationService)),
            BusRouterBuilder::createQueryBusByObject(self::getQueryBusByObjectsMapping($annotationRegistrationService)),
            BusRouterBuilder::createQueryBusByName(self::getQueryBusByNamesMapping($annotationRegistrationService)),
            BusRouterBuilder::createEventBusByObject(self::getEventBusByObjectsMapping($annotationRegistrationService)),
            BusRouterBuilder::createEventBusByName(self::getEventBusByNamesMapping($annotationRegistrationService))
        );
    }

    private static function isForTheSameAggregate(array $aggregateMethodUsage, $uniqueChannelName, string $oppositeMethodType, AnnotatedDefinition $registration): bool
    {
        return !isset($aggregateMethodUsage[$uniqueChannelName][$oppositeMethodType])
            || $aggregateMethodUsage[$uniqueChannelName][$oppositeMethodType]->getClassName() === $registration->getClassName();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $configuration
            ->registerMessageHandler($this->commandBusByObject)
            ->registerMessageHandler($this->commandBusByName)
            ->registerMessageHandler($this->queryBusByObject)
            ->registerMessageHandler($this->queryBusByName)
            ->registerMessageHandler($this->eventBusByObject)
            ->registerMessageHandler($this->eventBusByName);
    }

    public static function getCommandBusByObjectMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $uniqueChannels = [];
        $objectCommandHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, CommandHandler::class) as $registration) {
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            if ($classChannel) {
                $objectCommandHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration);
                $objectCommandHandlers[$classChannel] = array_unique($objectCommandHandlers[$classChannel]);
                $uniqueChannels[$classChannel][] = $registration;
            }
        }
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, CommandHandler::class) as $registration) {
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            if ($classChannel) {
                $objectCommandHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration);
                $objectCommandHandlers[$classChannel] = array_unique($objectCommandHandlers[$classChannel]);
                $uniqueChannels[$classChannel][] = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $objectCommandHandlers;
    }

    public static function getCommandBusByNamesMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $uniqueChannels = [];
        $namedCommandHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, CommandHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            if ($namedChannel) {
                $namedCommandHandlers[$namedChannel][] = $namedChannel;
                $namedCommandHandlers[$namedChannel] = array_unique($namedCommandHandlers[$namedChannel]);
                $uniqueChannels[$namedChannel][] = $registration;
            }
        }
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, CommandHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            if ($namedChannel) {
                $namedCommandHandlers[$namedChannel][] = $namedChannel;
                $namedCommandHandlers[$namedChannel] = array_unique($namedCommandHandlers[$namedChannel]);
                $uniqueChannels[$namedChannel][] = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $namedCommandHandlers;
    }

    public static function getQueryBusByObjectsMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $uniqueChannels = [];
        $objectQueryHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, QueryHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            if ($classChannel) {
                $objectQueryHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration);
                $objectQueryHandlers[$classChannel] = array_unique($objectQueryHandlers[$classChannel]);
                $uniqueChannels[$classChannel][] = $registration;
            }
        }
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, QueryHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            if ($classChannel) {
                $objectQueryHandlers[$classChannel][] = ModellingHandlerModule::getNamedMessageChannelFor($registration);
                $objectQueryHandlers[$classChannel] = array_unique($objectQueryHandlers[$classChannel]);
                $uniqueChannels[$classChannel][] = $registration;
            }
        }

        self::verifyUniqueness($uniqueChannels);

        return $objectQueryHandlers;
    }

    public static function getQueryBusByNamesMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $uniqueChannels = [];
        $namedQueryHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, QueryHandler::class) as $registration) {
            self::verifyInputChannel($registration);

            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            $namedQueryHandlers[$namedChannel][] = $namedChannel;
            $namedQueryHandlers[$namedChannel] = array_unique($namedQueryHandlers[$namedChannel]);
            $uniqueChannels[$namedChannel][] = $registration;
        }
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, QueryHandler::class) as $registration) {
            self::verifyInputChannel($registration);

            $namedChannel = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            $namedQueryHandlers[$namedChannel][] = $namedChannel;
            $namedQueryHandlers[$namedChannel] = array_unique($namedQueryHandlers[$namedChannel]);
            $uniqueChannels[$namedChannel][] = $registration;
        }

        self::verifyUniqueness($uniqueChannels);

        return $namedQueryHandlers;
    }

    public static function getEventBusByObjectsMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $objectEventHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, EventHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            $namedMessageChannelFor = ModellingHandlerModule::getNamedMessageChannelFor($registration);

            if ($classChannel) {
                $objectEventHandlers[$classChannel][] = $namedMessageChannelFor;
                $objectEventHandlers[$classChannel] = array_unique($objectEventHandlers[$classChannel]);
            }
        }
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, EventHandler::class) as $registration) {
            self::verifyInputChannel($registration);
            if (ModellingHandlerModule::hasMessageNameDefined($registration)) {
                continue;
            }

            $classChannel = ModellingHandlerModule::getPayloadClassIfAny($registration);
            $namedMessageChannelFor = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            if ($classChannel && !EventBusRouter::isRegexBasedRoute($namedMessageChannelFor)) {
                $objectEventHandlers[$classChannel][] = $namedMessageChannelFor;
                $objectEventHandlers[$classChannel] = array_unique($objectEventHandlers[$classChannel]);
            }
        }

        return $objectEventHandlers;
    }

    public static function getEventBusByNamesMapping(AnnotationFinder $annotationRegistrationService): array
    {
        $namedEventHandlers = [];
        foreach ($annotationRegistrationService->findCombined(MessageEndpoint::class, EventHandler::class) as $registration) {
            $chanelName = ModellingHandlerModule::getNamedMessageChannelFor($registration);

            $namedEventHandlers[$chanelName][] = $chanelName;
            $namedEventHandlers[$chanelName] = array_unique($namedEventHandlers[$chanelName]);
        }
        foreach ($annotationRegistrationService->findCombined(Aggregate::class, EventHandler::class) as $registration) {
            $channelName = ModellingHandlerModule::getNamedMessageChannelFor($registration);
            if (EventBusRouter::isRegexBasedRoute($channelName)) {
                throw ConfigurationException::create("Can not registered regex listen to channel for aggregates in {$registration}");
            }

            $namedEventHandlers[$channelName][] = $channelName;
            $namedEventHandlers[$channelName] = array_unique($namedEventHandlers[$channelName]);
        }
        return $namedEventHandlers;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
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
    public function getRelatedReferences(): array
    {
        return [];
    }

    private static function verifyInputChannel(AnnotatedDefinition $annotationRegistration) : void
    {
        if (!ModellingHandlerModule::getNamedMessageChannelFor($annotationRegistration) && !ModellingHandlerModule::getPayloadClassIfAny($annotationRegistration)) {
            throw ConfigurationException::create("Handler {$annotationRegistration->getClassName()}:{$annotationRegistration->getMethodName()} has no input channel information. Configure inputChannelName or type hint first argument as class");
        }
    }

    /**
     * @param AnnotatedDefinition[][] $uniqueChannels
     * @throws \Ecotone\Messaging\MessagingException
     */
    private static function verifyUniqueness(array $uniqueChannels): void
    {
        $notUniqueHandlerAnnotation = TypeDescriptor::create(NotUniqueHandler::class);
        $aggregateAnnotation = TypeDescriptor::create(Aggregate::class);
        foreach ($uniqueChannels as $uniqueChannelName => $registrations) {
            $combinedRegistrationNames = "";
            $registrationsToVerify = [];
            $aggregateMethodUsage = [];
            foreach ($registrations as $registration) {
                if ($registration->hasMethodAnnotation($notUniqueHandlerAnnotation)) {
                    continue;
                }

                if ($registration->hasClassAnnotation($aggregateAnnotation)) {
                    $isStatic   = (new \ReflectionMethod($registration->getClassName(), $registration->getMethodName()))->isStatic();
                    $methodType = $isStatic ? "factory" : "action";
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
}