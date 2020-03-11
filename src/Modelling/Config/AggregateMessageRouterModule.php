<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AggregateMessageRouterModule
 * @package Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AggregateMessageRouterModule implements AnnotationModule
{
    const AGGREGATE_ROUTER_MODULE = "aggregateRouterModule";

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
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $objectCommandHandlers = [];
        $namedCommandHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class) as $registration) {
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            $targetMessageChannel = AggregateMessagingModule::getMessageChannelFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectCommandHandlers[$class][] = $targetMessageChannel;
            }
            if ($annotationForMethod->inputChannelName) {
                $namedCommandHandlers[$annotationForMethod->inputChannelName][] = $targetMessageChannel;
            }
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, CommandHandler::class) as $registration) {
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            $targetMessageChannel = AggregateMessagingModule::getMessageChannelFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectCommandHandlers[$class][] = $targetMessageChannel;
            }
            if ($annotationForMethod->inputChannelName) {
                $namedCommandHandlers[$annotationForMethod->inputChannelName][] = $targetMessageChannel;
            }
        }
        $objectQueryHandlers = [];
        $namedQueryHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class) as $registration) {
            /** @var QueryHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            $targetMessageChannel = AggregateMessagingModule::getMessageChannelFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectQueryHandlers[$class][] = $targetMessageChannel;
            }
            if ($annotationForMethod->inputChannelName) {
                $namedQueryHandlers[$annotationForMethod->inputChannelName][] = $targetMessageChannel;
            }
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, QueryHandler::class) as $registration) {
            /** @var QueryHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            $targetMessageChannel = AggregateMessagingModule::getMessageChannelFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectQueryHandlers[$class][] = $targetMessageChannel;
            }
            if ($annotationForMethod->inputChannelName) {
                $namedQueryHandlers[$annotationForMethod->inputChannelName][] = $targetMessageChannel;
            }
        }
        $objectEventHandlers = [];
        $namedEventHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, EventHandler::class) as $registration) {
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectEventHandlers[$class][] = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            }
            if ($registration->getAnnotationForMethod()->listenTo) {
                $namedEventHandlers[$registration->getAnnotationForMethod()->listenTo][] = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            }
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EventHandler::class) as $registration) {
            $class = AggregateMessagingModule::getMessageClassFor($registration);
            if (!in_array($class, [null, TypeDescriptor::ARRAY])) {
                $objectEventHandlers[$class][] = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            }
            if ($registration->getAnnotationForMethod()->listenTo) {
                $namedEventHandlers[$registration->getAnnotationForMethod()->listenTo][] = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            }
        }

        return new self(
            BusRouterBuilder::createCommandBusByObject($objectCommandHandlers),
            BusRouterBuilder::createCommandBusByName($namedCommandHandlers),
            BusRouterBuilder::createQueryBusByObject($objectQueryHandlers),
            BusRouterBuilder::createQueryBusByName($namedQueryHandlers),
            BusRouterBuilder::createEventBusByObject($objectEventHandlers),
            BusRouterBuilder::createEventBusByName($namedEventHandlers)
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::AGGREGATE_ROUTER_MODULE;
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
}