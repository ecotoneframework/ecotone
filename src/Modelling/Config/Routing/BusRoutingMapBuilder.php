<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use function array_map;
use function array_unique;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\Attribute\EventHandler;
use RuntimeException;

use function str_contains;

class BusRoutingMapBuilder extends BusRoutingMap
{
    private array $channelsName = [];

    /**
     * @param array<RoutingEventHandler> $routingEventHandlers
     */
    public function __construct(private bool $isUnique = false, private array $routingEventHandlers = [], private ?Configuration $messagingConfiguration = null)
    {
        parent::__construct();
    }

    public function getRoutesFromAnnotatedFinding(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            $routingKey = $annotationForMethod->getListenTo();
        } else {
            $routingKey = $annotationForMethod->getInputChannelName();
        }

        if ($routingKey) {
            return [$routingKey];
        }

        $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        if ($interfaceToCall->hasNoParameters()) {
            return [];
        }
        $type = $interfaceToCall->getFirstParameter()->getTypeDescriptor();
        if ($type->isUnionType()) {
            $routes = [];
            foreach ($type->getUnionTypes() as $unionType) {
                $routes[] = (string) $unionType;
            }
            return $routes;
        } else {
            return [(string) $type];
        }
    }

    /**
     * @return string the destination channel name
     */
    public function addRoutesFromAnnotatedFinding(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): ?string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();
        $destinationChannel = $annotationForMethod->getEndpointId() . '.target';
        $routes = $this->getRoutesFromAnnotatedFinding($registration, $interfaceToCallRegistry);
        $priority = PriorityBasedOnType::fromAnnotatedFinding($registration);

        $routingEvent = new RoutingEvent($registration, $destinationChannel, $routes, $priority->getPriorityArray());

        return $this->dispatchRoutingEvent($routingEvent);
    }

    /**
     * @param string $routingKey
     * @param int|int[] $priority
     */
    public function addRoute(string $routingKey, string $channel, int|array $priority = 1): void
    {
        if ($routingKey === 'object') {
            $this->addCatchAllRoute($channel, $priority);
        } elseif (class_exists($routingKey)) {
            $this->addObjectRoute($routingKey, $channel, $priority);
        } else {
            $this->addNamedRoute($routingKey, $channel, $priority);
        }
    }

    /**
     * @param class-string|'object' $class
     * @param int|int[] $priority
     */
    public function addObjectRoute(string $class, string $channel, int|array $priority = 1): void
    {
        if ($class === 'object') {
            $this->addCatchAllRoute($channel, $priority);
        } else {
            $this->addChannel($channel, $priority);
            $this->objectRoutes[$class][] = $channel;
        }
    }


    /**
     * @param int|int[] $priority
     */
    public function addCatchAllRoute(string $channel, int|array $priority = 1): void
    {
        $this->addChannel($channel, $priority);
        $this->catchAllRoutes[] = $channel;
    }

    public function merge(self $routingConfig): void
    {
        foreach ($routingConfig->channelsPriority as $channel => $priority) {
            $this->addChannel($channel, $priority);
        }
        foreach ($routingConfig->objectRoutes as $class => $channels) {
            foreach ($channels as $channel) {
                $this->objectRoutes[$class][] = $channel;
            }
        }
        foreach ($routingConfig->catchAllRoutes as $channel) {
            $this->catchAllRoutes[] = $channel;
        }
        foreach ($routingConfig->namedRoutes as $routeName => $channels) {
            foreach ($channels as $channel) {
                $this->namedRoutes[$routeName][] = $channel;
            }
        }
        foreach ($routingConfig->regexRoutes as $pattern => $channels) {
            foreach ($channels as $channel) {
                $this->regexRoutes[$pattern][] = $channel;
            }
        }
    }

    /**
     * @param int|int[] $priority
     */
    public function addNamedRoute(string $routeName, string $channel, int|array $priority = 1): void
    {
        $this->addChannel($channel, $priority);

        if (str_contains($routeName, '*')) {
            $this->regexRoutes[$routeName][] = $channel;
        } else {
            $this->namedRoutes[$routeName][] = $channel;
        }
    }

    /**
     * @param class-string $class
     * @param string $routingKey
     */
    public function addObjectAlias(string $class, string $routingKey): void
    {
        if (isset($this->classToNameAliases[$class])) {
            throw ConfigurationException::create("Class $class already has an alias registered: " . $this->classToNameAliases[$class]);
        }
        if (isset($this->nameToClassAliases[$routingKey])) {
            throw ConfigurationException::create("Routing key $routingKey already has a class alias registered: " . $this->nameToClassAliases[$routingKey]);
        }

        $this->classToNameAliases[$class] = $routingKey;
        $this->nameToClassAliases[$routingKey] = $class;
    }

    public function optimize(array $routingKeysToOptimize = []): void
    {
        $this->optimizedRoutes = [];
        $this->objectRoutes = $this->uniqueRoutedChannels($this->objectRoutes);
        $this->namedRoutes = $this->uniqueRoutedChannels($this->namedRoutes);
        $this->regexRoutes = $this->uniqueRoutedChannels($this->regexRoutes);
        $this->catchAllRoutes = array_unique($this->catchAllRoutes);

        $allKnownRoutingKeys = array_merge(
            $routingKeysToOptimize,
            array_keys($this->objectRoutes),
            array_keys($this->namedRoutes),
            array_keys($this->classToNameAliases),
            array_keys($this->nameToClassAliases),
        );
        $allKnownRoutingKeys = array_unique($allKnownRoutingKeys);
        foreach ($allKnownRoutingKeys as $routingKey) {
            $this->optimizedRoutes[$routingKey] = $this->resolveWithoutOptimization($routingKey);
        }
    }

    public function getRoutingKeys(): array
    {
        $allKnownRoutingKeys = array_merge(
            array_keys($this->objectRoutes),
            array_keys($this->namedRoutes),
            array_keys($this->regexRoutes),
            array_keys($this->classToNameAliases),
            array_keys($this->nameToClassAliases),
        );
        return array_unique($allKnownRoutingKeys);
    }

    private function addChannel(string $channel, int|array $priority): void
    {
        if (! empty($this->optimizedRoutes)) {
            throw new RuntimeException("Cannot add channel $channel to routing config, because it is already optimized");
        }

        if (isset($this->channelsPriority[$channel]) && $this->channelsPriority[$channel] !== $priority) {
            throw new RuntimeException("Channel $channel is already registered with another priority");
        }
        $this->channelsPriority[$channel] = $priority;
    }

    public function compile(): Definition
    {
        $this->optimize();
        if ($this->isUnique) {
            foreach ($this->optimizedRoutes as $routingKey => $channels) {
                if (count($channels) > 1) {
                    throw ConfigurationException::create("Routing key $routingKey is registered with multiple channels: " . implode(', ', array_map($this->channelName(...), $channels)));
                }
            }
        }
        return new Definition(BusRoutingMap::class, [
            $this->channelsPriority,
            $this->objectRoutes,
            $this->catchAllRoutes,
            $this->namedRoutes,
            $this->regexRoutes,
            $this->classToNameAliases,
            $this->nameToClassAliases,
            $this->optimizedRoutes,
        ]);
    }

    private function channelName(string $channel): string
    {
        if (isset($this->channelsName[$channel])) {
            return $this->channelsName[$channel];
        }
        return $channel;
    }

    private function uniqueRoutedChannels(array $routes): array
    {
        $uniqueRoutes = [];
        foreach ($routes as $route => $channels) {
            $uniqueRoutes[$route] = array_unique($channels);
        }
        return $uniqueRoutes;
    }

    /**
     * @return ?string the destination channel name or null if the event is canceled
     */
    private function dispatchRoutingEvent(RoutingEvent $routingEvent): ?string
    {
        foreach ($this->routingEventHandlers as $routingEventHandler) {
            $routingEventHandler->handleRoutingEvent($routingEvent, $this->messagingConfiguration);
            if ($routingEvent->isCanceled()) {
                return null;
            }
            if ($routingEvent->isPropagationStopped()) {
                break;
            }
        }

        $destinationChannel = $routingEvent->getDestinationChannel();
        $priority = $routingEvent->getPriority();

        foreach ($routingEvent->getRoutingKeys() as $routingKey) {
            $this->addRoute($routingKey, $destinationChannel, $priority);
        }

        return $destinationChannel;
    }
}
