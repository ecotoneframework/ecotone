<?php

/*
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\NamedEvent;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\Attribute\Presend;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\Attribute\Transformer;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNode;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Modelling\Config\MessageHandlerRoutingModule;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Modelling\DecisionModel\DecisionModelHandlers;

final class MessageFlowDiscovery
{
    private const FRAMEWORK_NAMESPACE = 'Ecotone\\';

    /** @var array<string, FlowNode> */
    private array $nodes = [];
    /** @var list<FlowEdge> */
    private array $edges = [];
    /** @var array<string, array<string, string>> */
    private array $collaborators = [];
    /** @var array<string, bool> */
    private array $pollableChannels = [];
    /** @var array<string, list<string>> */
    private array $combinedChannels = [];
    /** @var array<string, string> */
    private array $ownerByClass = [];
    /** @var array<string, string> event name => event class */
    private array $namedEvents = [];
    /** @var array<string, AnnotatedFinding> */
    private array $handlerRegistrations = [];

    private function __construct(
        private readonly AnnotationFinder $annotationFinder,
        private readonly InterfaceToCallRegistry $interfaceToCallRegistry,
    ) {
    }

    /**
     * @param object[] $extensionObjects
     */
    public static function discover(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry, array $extensionObjects): DeclaredMessageFlow
    {
        return (new self($annotationFinder, $interfaceToCallRegistry))->run($extensionObjects);
    }

    /**
     * @param object[] $extensionObjects
     */
    private function run(array $extensionObjects): DeclaredMessageFlow
    {
        foreach (ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects) as $channelBuilder) {
            $this->pollableChannels[$channelBuilder->getMessageChannelName()] = $channelBuilder->isPollable();
            $this->channelNode($channelBuilder->getMessageChannelName());
        }
        foreach (ExtensionObjectResolver::resolve(CombinedMessageChannel::class, $extensionObjects) as $combinedChannel) {
            $this->combinedChannels[$combinedChannel->getReferenceName()] = $combinedChannel->combinedChannels;
        }

        $this->discoverNamedEvents();
        $this->discoverServiceMaps($extensionObjects);
        $this->discoverOwners();
        $this->discoverBusHandlers(CommandHandler::class, FlowNodeKind::COMMAND_HANDLER, FlowNodeKind::COMMAND);
        $this->discoverBusHandlers(QueryHandler::class, FlowNodeKind::QUERY_HANDLER, FlowNodeKind::QUERY);
        $this->discoverBusHandlers(EventHandler::class, FlowNodeKind::EVENT_HANDLER, FlowNodeKind::EVENT);
        $this->connectSubscribedEventChannels($extensionObjects);
        $this->discoverChannelHandlers();
        $this->discoverRouters();
        $this->discoverSplitters();
        $this->discoverTransformers();
        $this->discoverOrchestrators();
        $this->discoverInterceptors();
        $this->discoverAppliedEvents();
        $this->discoverGateways();
        $this->discoverScheduled();
        foreach (DecisionModelHandlers::scan($this->annotationFinder, $this->interfaceToCallRegistry)->appendingTheirResult() as $handler) {
            $id = 'handler:' . $handler->className() . '::' . $handler->methodName();
            if (isset($this->nodes[$id])) {
                $this->nodes[$id] = $this->nodes[$id]->withAttributes(['returnsEvents' => true]);
            }
        }

        return new DeclaredMessageFlow(MessageFlowGraph::create(array_values($this->nodes), $this->edges), $this->collaborators, $this->namedEvents);
    }

    /**
     * @param object[] $extensionObjects
     */
    private function discoverServiceMaps(array $extensionObjects): void
    {
        $serviceName = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults())->serviceName;
        foreach (ExtensionObjectResolver::resolve(DistributedServiceMap::class, $extensionObjects) as $serviceMap) {
            $bus = $this->distributedBusNode($serviceMap->getReferenceName());
            $subscriptions = array_map(fn (array $subscription) => $subscription['keys'], $serviceMap->eventSubscriptions);
            $this->nodes[$bus->id] = $bus->withAttributes(['commandMapping' => $serviceMap->commandMapping, 'eventSubscriptions' => $subscriptions]);
            foreach ($serviceMap->commandMapping as $targetService => $channelName) {
                $this->edges[] = new FlowEdge($bus->id, $this->channelNode($channelName)->id, FlowEdgeKind::SENDS_TO, ['targetService' => $targetService]);
                if ($targetService === $serviceName) {
                    $this->edges[] = new FlowEdge($this->channelNode($channelName)->id, $bus->id, FlowEdgeKind::INPUT);
                }
            }
            foreach ($serviceMap->eventSubscriptions as $channelName => $subscription) {
                if (in_array($serviceName, $subscription['exclude'], true) || ($subscription['include'] !== [] && ! in_array($serviceName, $subscription['include'], true))) {
                    continue;
                }
                $this->edges[] = new FlowEdge($bus->id, $this->channelNode($channelName)->id, FlowEdgeKind::SENDS_TO, ['subscriptionKeys' => $subscription['keys']]);
            }
        }
    }

    private function discoverNamedEvents(): void
    {
        foreach ($this->annotationFinder->findAnnotatedClasses(NamedEvent::class) as $eventClass) {
            if (str_starts_with($eventClass, self::FRAMEWORK_NAMESPACE)) {
                continue;
            }
            /** @var NamedEvent $namedEvent */
            $namedEvent = $this->annotationFinder->getAttributeForClass($eventClass, NamedEvent::class);
            $this->namedEvents[$namedEvent->getName()] = $eventClass;
        }
        foreach ($this->applicationMethods(EventHandler::class) as $registration) {
            foreach (MessageHandlerRoutingModule::getFirstParameterClassesIfAny($registration, $this->interfaceToCallRegistry) as $eventClass) {
                $definition = $this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($eventClass));
                if ($definition->hasClassAnnotation(Type::attribute(NamedEvent::class))) {
                    $this->namedEvents[$definition->getSingleClassAnnotation(Type::attribute(NamedEvent::class))->getName()] = $eventClass;
                }
            }
        }
    }

    private function discoverOwners(): void
    {
        foreach ($this->annotationFinder->findAnnotatedClasses(Aggregate::class) as $aggregateClass) {
            $isSaga = $this->annotationFinder->findAttributeForClass($aggregateClass, Saga::class) !== null;
            $kind = $isSaga ? FlowNodeKind::SAGA : FlowNodeKind::AGGREGATE;
            $id = $kind->value . ':' . $aggregateClass;
            $this->nodes[$id] = new FlowNode($id, $kind, $this->shortName($aggregateClass), [
                'class' => $aggregateClass,
                'eventSourced' => $this->annotationFinder->findAttributeForClass($aggregateClass, EventSourcingAggregate::class) !== null,
            ]);
            $this->ownerByClass[$aggregateClass] = $id;
        }
        foreach ($this->annotationFinder->findAnnotatedClasses(Projection::class) as $projectionClass) {
            /** @var Projection $projection */
            $projection = $this->annotationFinder->getAttributeForClass($projectionClass, Projection::class);
            $id = 'projection:' . $projection->getName();
            $this->nodes[$id] = new FlowNode($id, FlowNodeKind::PROJECTION, $projection->getName(), ['class' => $projectionClass]);
            $polling = $this->annotationFinder->findAttributeForClass($projectionClass, Polling::class);
            if ($polling instanceof Polling) {
                $this->nodes[$id] = $this->nodes[$id]->withAttributes(['endpointId' => $polling->endpointId]);
            }
            $streaming = $this->annotationFinder->findAttributeForClass($projectionClass, Streaming::class);
            if ($streaming instanceof Streaming) {
                $this->edges[] = new FlowEdge($this->channelNode($streaming->channelName)->id, $id, FlowEdgeKind::INPUT);
            }
            foreach ([ProjectionBackfill::class, ProjectionRebuild::class] as $replayAttribute) {
                $replay = $this->annotationFinder->findAttributeForClass($projectionClass, $replayAttribute);
                if (($replay instanceof ProjectionBackfill || $replay instanceof ProjectionRebuild) && $replay->asyncChannelName !== null) {
                    $channelId = $this->channelNode($replay->asyncChannelName)->id;
                    $this->edges[] = new FlowEdge($id, $channelId, FlowEdgeKind::ASYNCHRONOUS);
                    $this->edges[] = new FlowEdge($channelId, $id, FlowEdgeKind::INPUT);
                }
            }
            $this->ownerByClass[$projectionClass] = $id;
        }
    }

    private function discoverBusHandlers(string $attribute, FlowNodeKind $handlerKind, FlowNodeKind $messageKind): void
    {
        $routing = new BusRoutingMapBuilder();
        foreach ($this->applicationMethods($attribute) as $registration) {
            /** @var CommandHandler|QueryHandler|EventHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $routingKeys = $routing->getRoutesFromAnnotatedFinding($registration, $this->interfaceToCallRegistry);
            $distributed = $registration->hasAnnotation(Distributed::class);
            $handlerId = $this->registerHandler($registration, $handlerKind, ['routingKeys' => $routingKeys, 'distributed' => $distributed]);

            foreach ($routingKeys as $routingKey) {
                $message = $this->messageNode($routingKey, $messageKind, handled: true);
                $this->edges[] = new FlowEdge($message->id, $handlerId, FlowEdgeKind::ROUTED_TO);
                if ($distributed) {
                    /** @var Distributed $distributedAttribute */
                    $distributedAttribute = $registration->getAnnotationsByImportanceOrder(Distributed::class)[0];
                    $this->nodes[$message->id] = $message->withAttributes(['distributed' => true]);
                    $this->edges[] = new FlowEdge($this->distributedBusNode($distributedAttribute->distributionReference)->id, $message->id, FlowEdgeKind::DISTRIBUTES);
                }
            }
            $this->registerOutput($handlerId, $annotation->getOutputChannelName());
            if ($handlerKind !== FlowNodeKind::QUERY_HANDLER) {
                $this->registerAsynchronous($handlerId, $registration);
            }
        }
    }

    private function discoverChannelHandlers(): void
    {
        foreach ($this->applicationMethods(InternalHandler::class) as $registration) {
            /** @var InternalHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $handlerId = $this->registerHandler($registration, FlowNodeKind::INTERNAL_HANDLER, ['inputChannel' => $annotation->getInputChannelName()]);
            $this->edges[] = new FlowEdge($this->channelNode($annotation->getInputChannelName())->id, $handlerId, FlowEdgeKind::INPUT);
            if ($registration->hasAnnotation(Distributed::class)) {
                $this->registerDistributedChannelHandler($handlerId, $annotation->getInputChannelName(), $registration);
            }
            $this->registerOutput($handlerId, $annotation->getOutputChannelName());
            $this->registerAsynchronous($handlerId, $registration);
        }
    }

    private function registerDistributedChannelHandler(string $handlerId, string $inputChannelName, AnnotatedFinding $registration): void
    {
        /** @var Distributed $distributedAttribute */
        $distributedAttribute = $registration->getAnnotationsByImportanceOrder(Distributed::class)[0];
        $busId = $this->distributedBusNode($distributedAttribute->distributionReference)->id;
        $this->edges[] = new FlowEdge($busId, $this->channelNode($inputChannelName)->id, FlowEdgeKind::DISTRIBUTES);
        if ($this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->canReturnValue()) {
            $this->edges[] = new FlowEdge($handlerId, $busId, FlowEdgeKind::SENDS_TO, ['via' => 'replyRoutingKey']);
        }
    }

    private function discoverRouters(): void
    {
        foreach ($this->applicationMethods(Router::class) as $registration) {
            /** @var Router $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $handlerId = $this->registerHandler($registration, FlowNodeKind::ROUTER, ['inputChannel' => $annotation->getInputChannelName()]);
            $this->edges[] = new FlowEdge($this->channelNode($annotation->getInputChannelName())->id, $handlerId, FlowEdgeKind::INPUT);
        }
    }

    private function discoverSplitters(): void
    {
        foreach ($this->applicationMethods(Splitter::class) as $registration) {
            /** @var Splitter $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $handlerId = $this->registerHandler($registration, FlowNodeKind::SPLITTER, ['inputChannel' => $annotation->getInputChannelName()]);
            $this->edges[] = new FlowEdge($this->channelNode($annotation->getInputChannelName())->id, $handlerId, FlowEdgeKind::INPUT);
            $this->registerOutput($handlerId, $annotation->getOutputChannelName());
        }
    }

    private function discoverTransformers(): void
    {
        foreach ($this->applicationMethods(Transformer::class) as $registration) {
            /** @var Transformer $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $handlerId = $this->registerHandler($registration, FlowNodeKind::TRANSFORMER, ['inputChannel' => $annotation->getInputChannelName()]);
            $this->edges[] = new FlowEdge($this->channelNode($annotation->getInputChannelName())->id, $handlerId, FlowEdgeKind::INPUT);
            $this->registerOutput($handlerId, $annotation->getOutputChannelName());
        }
    }

    /**
     * @param object[] $extensionObjects
     */
    private function connectSubscribedEventChannels(array $extensionObjects): void
    {
        foreach (ExtensionObjectResolver::resolve(DistributedServiceMap::class, $extensionObjects) as $serviceMap) {
            $busId = $this->distributedBusNode($serviceMap->getReferenceName())->id;
            foreach ($serviceMap->eventSubscriptions as $channelName => $subscription) {
                if ($this->subscribesToAnyOf($subscription['keys'])) {
                    $this->edges[] = new FlowEdge($this->channelNode($channelName)->id, $busId, FlowEdgeKind::INPUT);
                }
            }
        }
    }

    /**
     * @param string[] $subscriptionKeys
     */
    private function subscribesToAnyOf(array $subscriptionKeys): bool
    {
        foreach ($this->nodes as $node) {
            if ($node->kind !== FlowNodeKind::EVENT_HANDLER || ($node->attributes['distributed'] ?? false) !== true) {
                continue;
            }
            foreach ($node->attributes['routingKeys'] ?? [] as $routingKey) {
                foreach ($subscriptionKeys as $subscriptionKey) {
                    if (BusRoutingMap::globMatch($subscriptionKey, (string) $routingKey)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function discoverOrchestrators(): void
    {
        foreach ($this->applicationMethods(Orchestrator::class) as $registration) {
            /** @var Orchestrator $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $handlerId = $this->registerHandler($registration, FlowNodeKind::ORCHESTRATOR, ['inputChannel' => $annotation->getInputChannelName()]);
            $this->edges[] = new FlowEdge($this->channelNode($annotation->getInputChannelName())->id, $handlerId, FlowEdgeKind::INPUT);
        }
    }

    private function discoverInterceptors(): void
    {
        $parameterConverters = ParameterConverterAnnotationFactory::create();
        foreach (['presend' => Presend::class, 'before' => Before::class, 'around' => Around::class, 'after' => After::class] as $position => $attribute) {
            foreach ($this->applicationMethods($attribute) as $registration) {
                /** @var Presend|Before|Around|After $annotation */
                $annotation = $registration->getAnnotationForMethod();
                $interface = $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
                $pointcut = $annotation->pointcut !== ''
                    ? Pointcut::createWith($annotation->pointcut)
                    : Pointcut::initializeFrom($interface, $parameterConverters->createParameterConverters($interface));
                $id = 'interceptor:' . $registration->getClassName() . '::' . $registration->getMethodName();
                $this->nodes[$id] = new FlowNode($id, FlowNodeKind::INTERCEPTOR, $this->shortName($registration->getClassName()) . '::' . $registration->getMethodName(), [
                    'class' => $registration->getClassName(),
                    'method' => $registration->getMethodName(),
                    'position' => $position,
                    'pointcut' => $annotation->pointcut,
                ]);
                $this->collaborators[$id] = $this->collaboratorsOf($registration);
                foreach ($this->handlerRegistrations as $handlerId => $handler) {
                    if ($pointcut->doesItCut($this->interfaceToCallRegistry->getFor($handler->getClassName(), $handler->getMethodName()), [$handler->getAnnotationForMethod()])) {
                        $this->edges[] = new FlowEdge($handlerId, $id, FlowEdgeKind::INTERCEPTED_BY);
                    }
                }
            }
        }
    }

    private function discoverAppliedEvents(): void
    {
        foreach ($this->applicationMethods(Apply::class) as $registration) {
            $ownerId = $this->ownerByClass[$registration->getClassName()] ?? null;
            if ($ownerId === null) {
                continue;
            }
            foreach (MessageHandlerRoutingModule::getFirstParameterClassesIfAny($registration, $this->interfaceToCallRegistry) as $eventClass) {
                $this->edges[] = new FlowEdge($ownerId, $this->messageNode($eventClass, FlowNodeKind::EVENT, handled: false)->id, FlowEdgeKind::APPLIES, ['method' => $registration->getMethodName()]);
            }
        }
    }

    private function discoverGateways(): void
    {
        foreach ($this->applicationMethods(MessageGateway::class) as $registration) {
            /** @var MessageGateway $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $id = 'gateway:' . $registration->getClassName() . '::' . $registration->getMethodName();
            $this->nodes[$id] = new FlowNode($id, FlowNodeKind::GATEWAY, $this->shortName($registration->getClassName()) . '::' . $registration->getMethodName(), [
                'interface' => $registration->getClassName(),
                'method' => $registration->getMethodName(),
                'requestChannel' => $annotation->requestChannel,
            ]);
            $this->edges[] = new FlowEdge($id, $this->channelNode($annotation->requestChannel)->id, FlowEdgeKind::SENDS_TO);
        }
    }

    private function discoverScheduled(): void
    {
        foreach ($this->applicationMethods(Scheduled::class) as $registration) {
            /** @var Scheduled $annotation */
            $annotation = $registration->getAnnotationForMethod();
            $id = 'scheduled:' . $registration->getClassName() . '::' . $registration->getMethodName();
            $this->collaborators[$id] = $this->collaboratorsOf($registration);
            $this->nodes[$id] = new FlowNode($id, FlowNodeKind::SCHEDULED, $this->shortName($registration->getClassName()) . '::' . $registration->getMethodName(), [
                'class' => $registration->getClassName(),
                'method' => $registration->getMethodName(),
                'endpointId' => $annotation->getEndpointId(),
            ]);
            if ($annotation->requestChannelName !== NullableMessageChannel::CHANNEL_NAME) {
                $this->edges[] = new FlowEdge($id, $this->channelNode($annotation->requestChannelName)->id, FlowEdgeKind::SENDS_TO);
            }
        }
    }

    /**
     * @param array<string, scalar|array<scalar>|null> $attributes
     */
    private function registerHandler(AnnotatedFinding $registration, FlowNodeKind $kind, array $attributes): string
    {
        /** @var EndpointAnnotation $annotation */
        $annotation = $registration->getAnnotationForMethod();
        $id = 'handler:' . $registration->getClassName() . '::' . $registration->getMethodName();
        if (isset($this->nodes[$id]) && $this->nodes[$id]->kind !== $kind) {
            $id .= ':' . $kind->value;
        }
        if (isset($this->nodes[$id]->attributes['routingKeys'], $attributes['routingKeys'])) {
            $attributes['routingKeys'] = array_values(array_unique([...$this->nodes[$id]->attributes['routingKeys'], ...$attributes['routingKeys']]));
        }
        $this->nodes[$id] = new FlowNode($id, $kind, $this->shortName($registration->getClassName()) . '::' . $registration->getMethodName(), array_merge([
            'class' => $registration->getClassName(),
            'method' => $registration->getMethodName(),
            'endpointId' => $annotation->isEndpointIdGenerated() ? null : $annotation->getEndpointId(),
            'owner' => $this->ownerByClass[$registration->getClassName()] ?? null,
        ], $attributes));
        $this->collaborators[$id] = $this->collaboratorsOf($registration);
        $this->handlerRegistrations[$id] = $registration;

        return $id;
    }

    private function registerOutput(string $handlerId, string $outputChannelName): void
    {
        if ($outputChannelName === '') {
            return;
        }
        $this->edges[] = new FlowEdge($handlerId, $this->channelNode($outputChannelName)->id, FlowEdgeKind::OUTPUT);
    }

    private function registerAsynchronous(string $handlerId, AnnotatedFinding $registration): void
    {
        if (! $registration->hasAnnotation(Asynchronous::class)) {
            return;
        }
        /** @var Asynchronous $attribute */
        $attribute = $registration->getAnnotationsByImportanceOrder(Asynchronous::class)[0];
        foreach ($attribute->getChannelName() as $channelName) {
            foreach ($this->combinedChannels[$channelName] ?? [$channelName] as $resolvedChannelName) {
                $this->pollableChannels[$resolvedChannelName] = true;
                $this->edges[] = new FlowEdge($this->channelNode($resolvedChannelName)->id, $handlerId, FlowEdgeKind::ASYNCHRONOUS);
            }
        }
    }

    private function messageNode(string $routingKey, FlowNodeKind $kind, bool $handled): FlowNode
    {
        $routingKey = $this->namedEvents[$routingKey] ?? $routingKey;
        $id = 'message:' . $routingKey;
        $isClass = class_exists($routingKey) || interface_exists($routingKey);
        $existing = $this->nodes[$id] ?? null;
        if ($existing !== null) {
            return $handled && ! $existing->attributes['handled']
                ? $this->nodes[$id] = $existing->withAttributes(['handled' => true])
                : $existing;
        }

        return $this->nodes[$id] = new FlowNode($id, $kind, $isClass ? $this->shortName($routingKey) : $routingKey, [
            'routingKey' => $routingKey,
            'class' => $isClass ? $routingKey : null,
            'handled' => $handled,
        ] + $this->namedAttributeOf($routingKey));
    }

    /**
     * @return array{namedAs?: string}
     */
    private function namedAttributeOf(string $eventClass): array
    {
        $name = array_search($eventClass, $this->namedEvents, true);

        return $name === false ? [] : ['namedAs' => $name];
    }

    private function distributedBusNode(string $reference): FlowNode
    {
        $id = 'distributed:' . $reference;

        return $this->nodes[$id] ??= new FlowNode($id, FlowNodeKind::DISTRIBUTED_BUS, self::distributedBusLabel($reference), ['reference' => $reference]);
    }

    public static function distributedBusLabel(string $reference): string
    {
        $segments = explode('\\', $reference);

        return $reference === DistributedBus::class ? 'Distributed Bus' : 'Distributed Bus: ' . end($segments);
    }

    private function channelNode(string $channelName): FlowNode
    {
        $id = 'channel:' . $channelName;
        $pollable = $this->pollableChannels[$channelName] ?? false;
        $existing = $this->nodes[$id] ?? null;
        if ($existing !== null && $existing->attributes['pollable'] === $pollable) {
            return $existing;
        }

        return $this->nodes[$id] = new FlowNode($id, FlowNodeKind::CHANNEL, $channelName, ['name' => $channelName, 'pollable' => $pollable]);
    }

    /**
     * @return array<string, string>
     */
    private function collaboratorsOf(AnnotatedFinding $registration): array
    {
        $collaborators = [];
        foreach ($this->interfaceToCallRegistry->getClassDefinitionFor(Type::object($registration->getClassName()))->getProperties() as $property) {
            $class = $this->classOf($property->getType());
            if ($class !== null) {
                $collaborators['this.' . $property->getName()] = $class;
            }
        }
        foreach ($this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->getInterfaceParameters() as $parameter) {
            $class = $this->classOf($parameter->getTypeDescriptor());
            if ($class !== null) {
                $collaborators[$parameter->getName()] = $class;
            }
        }

        return $collaborators;
    }

    private function classOf(Type $type): ?string
    {
        foreach ($type->getUnionTypes() as $unionType) {
            if ($unionType->isClassOrInterface()) {
                return $unionType->toString();
            }
        }

        return null;
    }

    /**
     * @return list<AnnotatedFinding>
     */
    private function applicationMethods(string $attribute): array
    {
        return array_values(array_filter(
            $this->annotationFinder->findAnnotatedMethods($attribute),
            fn (AnnotatedFinding $registration) => ! str_starts_with($registration->getClassName(), self::FRAMEWORK_NAMESPACE)
        ));
    }

    private function shortName(string $className): string
    {
        $segments = explode('\\', $className);

        return end($segments);
    }
}
