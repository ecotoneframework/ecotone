<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Router\RouterProcessor;
use Ecotone\Messaging\Handler\Router\RouteToChannelResolver;
use Ecotone\Modelling\Config\Routing\BusRouteSelector;
use Ecotone\Modelling\Config\Routing\BusRoutingKeyResolver;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Projecting\EcotoneProjectorExecutor;
use Ecotone\Projecting\ProjectingHeaders;

class EcotoneProjectionExecutorBuilder implements ProjectionExecutorBuilder
{
    private const DEFAULT_BATCH_SIZE = 1_000;

    /**
     * @param AnnotatedDefinition[] $projectionEventHandlers
     */
    public function __construct(
        private string  $projectionName,
        private ?string $partitionHeader = null,
        private bool    $automaticInitialization = true,
        private array   $namedEvents = [],
        private ?string $initChannel = null,
        private ?string $deleteChannel = null,
        private ?string $flushChannel = null,
        private array   $projectionEventHandlers = [],
        private ?string $asyncChannelName = null,
        private ?int    $batchSize = null,
    ) {
        if ($this->partitionHeader && ! $this->automaticInitialization) {
            throw new ConfigurationException("Cannot set partition header for projection {$this->projectionName} with automatic initialization disabled");
        }
    }

    public function projectionName(): string
    {
        return $this->projectionName;
    }

    public function partitionHeader(): ?string
    {
        return $this->partitionHeader;
    }

    public function asyncChannelName(): ?string
    {
        return $this->asyncChannelName;
    }

    public function addEventHandler(AnnotatedDefinition $eventHandler): void
    {
        $this->projectionEventHandlers[] = $eventHandler;
    }

    public function setInitChannel(?string $initChannel): void
    {
        $this->initChannel = $initChannel;
    }

    public function setDeleteChannel(?string $deleteChannel): void
    {
        $this->deleteChannel = $deleteChannel;
    }

    public function setFlushChannel(string $inputChannel): void
    {
        $this->flushChannel = $inputChannel;
    }

    public function setAsyncChannel(string $asynchronousChannelName): void
    {
        $this->asyncChannelName = $asynchronousChannelName;
    }

    public function automaticInitialization(): bool
    {
        return $this->automaticInitialization;
    }

    public function batchSize(): int
    {
        return $this->batchSize ?? self::DEFAULT_BATCH_SIZE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $routerProcessor = $this->buildExecutionRouter($builder);
        return new Definition(EcotoneProjectorExecutor::class, [
            new Reference(MessagingEntrypoint::class), // Headers propagation is required for EventStreamEmitter
            $this->projectionName,
            $routerProcessor,
            $this->initChannel,
            $this->deleteChannel,
            $this->flushChannel,
        ]);
    }

    private function buildExecutionRouter(MessagingContainerBuilder $builder): Definition
    {
        $routerMap = new BusRoutingMapBuilder();
        foreach ($this->projectionEventHandlers as $eventHandler) {
            $routerMap->addRoutesFromAnnotatedFinding($eventHandler, $builder->getInterfaceToCallRegistry());
        }
        foreach ($this->namedEvents as $className => $eventName) {
            $routerMap->addObjectAlias($className, $eventName);
        }

        return new Definition(RouterProcessor::class, [
            new Definition(BusRouteSelector::class, [
                $routerMap->compile(),
                new Definition(BusRoutingKeyResolver::class, [ProjectingHeaders::PROJECTION_EVENT_NAME]),
            ]),
            new Definition(RouteToChannelResolver::class, [new Reference(ChannelResolver::class)]),
            true,
        ]);
    }
}
