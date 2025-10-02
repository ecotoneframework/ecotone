<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use function array_unique;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NamedEvent;
use RuntimeException;

use function str_contains;

class BusRoutingMapBuilder extends BusRoutingMap
{
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

        $type = $this->getFirstParameterType($interfaceToCallRegistry, $registration);
        if (! $type) {
            return [];
        } else {
            $routes = [];
            foreach ($type->getUnionTypes() as $unionType) {
                $routes[] = (string) $unionType;
            }
            return $routes;
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

        $routingEvent = new RoutingEvent($this, $registration, $destinationChannel, $routes, $priority->getPriorityArray());

        $this->dispatchRoutingEvent($routingEvent);

        if ($routingEvent->isCanceled()) {
            return null; // event is canceled, no routing
        }
        $destinationChannel = $routingEvent->getDestinationChannel();
        $priority = $routingEvent->getPriority();

        foreach ($routingEvent->getRoutingKeys() as $routingKey) {
            $this->addRoute($routingKey, $destinationChannel, $priority);
        }

        // add object alias if the routing key is a class and has a NamedEvent annotation
        $type = $this->getFirstParameterType($interfaceToCallRegistry, $registration);
        if ($type) {
            foreach ($type->getUnionTypes() as $unionType) {
                $className = (string) $unionType;
                if (class_exists($className)) {
                    $classDefinition = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($className));
                    if ($classDefinition->hasClassAnnotation(Type::attribute(NamedEvent::class))) {
                        $namedEvent = $classDefinition->getSingleClassAnnotation(Type::attribute(NamedEvent::class));
                        $this->addObjectAlias($className, $namedEvent->getName());
                    }
                }
            }
        }

        return $destinationChannel;
    }

    /**
     * @param string $routingKey
     * @param int|int[] $priority
     */
    public function addRoute(string $routingKey, string $channel, int|array $priority = 1): void
    {
        if ($routingKey === 'object') {
            $this->addCatchAllRoute($channel, $priority);
        } elseif (class_exists($routingKey) || interface_exists($routingKey)) {
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
            $this->addChannelToRoute($this->objectRoutes, $class, $channel, $priority);
        }
    }


    /**
     * @param int|int[] $priority
     */
    public function addCatchAllRoute(string $channel, int|array $priority = 1): void
    {
        if (isset($this->catchAllRoutes[$channel])) {
            throw new RuntimeException("Catch all route for channel $channel is already registered with another priority");
        }
        $this->catchAllRoutes[$channel] = $priority;
    }

    public function merge(self $routingConfig): void
    {
        foreach ($routingConfig->objectRoutes as $class => $channels) {
            foreach ($channels as $channel => $priority) {
                $this->addChannelToRoute($this->objectRoutes, $class, $channel, $priority);
            }
        }
        foreach ($routingConfig->catchAllRoutes as $channel => $priority) {
            $this->addCatchAllRoute($channel, $priority);
        }
        foreach ($routingConfig->namedRoutes as $routeName => $channels) {
            foreach ($channels as $channel => $priority) {
                $this->addChannelToRoute($this->namedRoutes, $routeName, $channel, $priority);
            }
        }
        foreach ($routingConfig->regexRoutes as $pattern => $channels) {
            foreach ($channels as $channel => $priority) {
                $this->addChannelToRoute($this->regexRoutes, $pattern, $channel, $priority);
            }
        }
    }

    /**
     * @param int|int[] $priority
     */
    public function addNamedRoute(string $routeName, string $channel, int|array $priority = 1): void
    {
        if (str_contains($routeName, '*')) {
            $this->addChannelToRoute($this->regexRoutes, $routeName, $channel, $priority);
        } else {
            $this->addChannelToRoute($this->namedRoutes, $routeName, $channel, $priority);
        }
    }

    /**
     * @param class-string $class
     * @param string $routingKey
     */
    public function addObjectAlias(string $class, string $routingKey): void
    {
        if (isset($this->classToNameAliases[$class])) {
            if ($this->classToNameAliases[$class] === $routingKey) {
                return; // already registered
            }
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

    private function addChannelToRoute(array &$routes, string $routingKey, string $channel, int|array $priority): void
    {
        if (! empty($this->optimizedRoutes)) {
            throw new RuntimeException("Cannot add channel $channel to routing config, because it is already optimized");
        }

        if (isset($routes[$routingKey][$channel])) {
            throw new RuntimeException("Channel $channel is already registered with another priority");
        }
        $routes[$routingKey][$channel] = $priority;
    }

    public function compile(): Definition
    {
        $this->optimize();
        if ($this->isUnique) {
            foreach ($this->optimizedRoutes as $routingKey => $channels) {
                if (count($channels) > 1) {
                    throw ConfigurationException::create("Routing key $routingKey is registered with multiple channels: " . implode(', ', $channels));
                }
            }
        }
        return new Definition(BusRoutingMap::class, [
            $this->objectRoutes,
            $this->catchAllRoutes,
            $this->namedRoutes,
            $this->regexRoutes,
            $this->classToNameAliases,
            $this->nameToClassAliases,
            $this->optimizedRoutes,
        ]);
    }

    public function getMessagingConfiguration(): ?Configuration
    {
        return $this->messagingConfiguration;
    }

    /**
     * @return ?string the destination channel name or null if the event is canceled
     */
    private function dispatchRoutingEvent(RoutingEvent $routingEvent): void
    {
        foreach ($this->routingEventHandlers as $routingEventHandler) {
            $routingEventHandler->handleRoutingEvent($routingEvent);
            if ($routingEvent->isCanceled() || $routingEvent->isPropagationStopped()) {
                return;
            }
        }
    }

    private function getFirstParameterType(InterfaceToCallRegistry $interfaceToCallRegistry, AnnotatedFinding $registration): ?Type
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        if ($interfaceToCall->hasNoParameters()) {
            return null;
        }
        return $interfaceToCall->getFirstParameter()->getTypeDescriptor();
    }
}
