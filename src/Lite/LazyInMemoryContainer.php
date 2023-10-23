<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;

class LazyInMemoryContainer implements ContainerInterface
{
    private array $resolvedObjects = [];

    public function __construct(private array $definitions, private ?ContainerInterface $externalContainer = null)
    {
        $this->resolvedObjects[ContainerInterface::class] = $this;
    }

    public function get(string $id)
    {
        return $this->resolveReference(new Reference($id));
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->resolvedObjects[$id]) || ($this->externalContainer?->has($id) ?? false);
    }

    public function set(string $id, mixed $object): void
    {
        $this->resolvedObjects[$id] = $object;
    }

    private function resolveArgument(mixed $argument): mixed
    {
        if (is_array($argument)) {
            return array_map(fn ($argument) => $this->resolveArgument($argument), $argument);
        } elseif($argument instanceof Definition) {
            $object = $this->instantiateDefinition($argument);
            foreach ($argument->getMethodCalls() as $methodCall) {
                $object->{$methodCall->getMethodName()}(...$this->resolveArgument($methodCall->getArguments()));
            }
            return $object;
        } elseif ($argument instanceof Reference) {
            return $this->resolveReference($argument);
        } else {
            return $argument;
        }
    }
    private function instantiateDefinition(Definition $definition): mixed
    {
        if ($definition instanceof DefinedObjectWrapper) {
            return $definition->instance();
        }

        $arguments = $this->resolveArgument($definition->getArguments());
        if ($definition->hasFactory()) {
            $factory = $definition->getFactory();
            if (method_exists($factory[0], $factory[1]) && (new ReflectionMethod($factory[0], $factory[1]))->isStatic()) {
                // static call
                return $factory(...$arguments);
            } else {
                // method call from a service instance
                $service = $this->resolveReference(new Reference($factory[0]));
                return $service->{$factory[1]}(...$arguments);
            }
        } else {
            $class = $definition->getClassName();
            return new $class(...$arguments);
        }
    }

    private function resolveReference(Reference $reference): mixed
    {
        $id = $reference->getId();
        if (isset($this->resolvedObjects[$id])) {
            return $this->resolvedObjects[$id];
        }
        if (isset($this->definitions[$id])) {
            return $this->resolvedObjects[$id] = $this->resolveArgument($this->definitions[$id]);
        }
        if ($this->externalContainer?->has($id)) {
            return $this->resolvedObjects[$id] = $this->externalContainer->get($id);
        }
        if ($this->externalContainer?->has(InMemoryContainerImplementation::ALIAS_PREFIX . $id)) {
            return $this->externalContainer->get(InMemoryContainerImplementation::ALIAS_PREFIX . $id);
        }
        // This is the only default service we provide
        if ($id === 'logger' || $id === LoggerInterface::class) {
            $alias = $id === 'logger' ? LoggerInterface::class : 'logger';
            $logger = $this->externalContainer?->has($alias) ? $this->externalContainer->get($alias) : new NullLogger();
            $this->resolvedObjects['logger'] = $logger;
            $this->resolvedObjects[LoggerInterface::class] = $logger;
            return $logger;
        }
        if ($reference->getInvalidBehavior() === ContainerImplementation::NULL_ON_INVALID_REFERENCE) {
            return null;
        }
        throw new InvalidArgumentException("Reference {$id} was not found in definitions");
    }
}
