<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AggregateMessageRouterModule
 * @package SimplyCodedSoftware\DomainModel\Config
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
        $commandHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class) as $registration) {
            $commandHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, CommandHandler::class) as $registration) {
            $commandHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }
        $queryHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class) as $registration) {
            $queryHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, QueryHandler::class) as $registration) {
            $queryHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }
        $eventHandlers = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(Aggregate::class, EventHandler::class) as $registration) {
            $eventHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EventHandler::class) as $registration) {
            $eventHandlers[AggregateMessagingModule::getMessageClassFor($registration)][] = AggregateMessagingModule::getMessageChannelFor($registration);
        }

        return new self(
            BusRouterBuilder::createCommandBusByObject($commandHandlers),
            BusRouterBuilder::createCommandBusByName($commandHandlers),
            BusRouterBuilder::createQueryBusByObject($queryHandlers),
            BusRouterBuilder::createQueryBusByName($queryHandlers),
            BusRouterBuilder::createEventBusByObject($eventHandlers),
            BusRouterBuilder::createEventBusByName($eventHandlers)
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
            ->registerMessageHandler($this->queryBusByObject)
            ->registerMessageHandler($this->eventBusByObject)
            ->registerMessageHandler($this->commandBusByName)
            ->registerMessageHandler($this->queryBusByName)
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
    public function getRequiredReferences(): array
    {
        return [];
    }
}