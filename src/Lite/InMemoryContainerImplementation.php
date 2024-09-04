<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;

use function get_class;

use InvalidArgumentException;

use function is_object;
use function method_exists;

use Psr\Container\ContainerInterface;
use ReflectionMethod;

use function str_starts_with;

/**
 * licence Apache-2.0
 */
class InMemoryContainerImplementation implements ContainerImplementation
{
    public const ALIAS_PREFIX = 'ecotone.testing.';
    public function __construct(private InMemoryPSRContainer $container, private ?ContainerInterface $externalContainer = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $builder): void
    {
        $this->container->set(ContainerInterface::class, $this->container);
        foreach ($builder->getDefinitions() as $id => $definition) {
            if (! $this->container->has($id)) {
                $object = $this->resolveArgument($definition, $builder);
                $this->container->set($id, $object);
            }
        }
    }

    private function resolveArgument(mixed $argument, ContainerBuilder $builder): mixed
    {
        if (is_array($argument)) {
            return array_map(fn ($argument) => $this->resolveArgument($argument, $builder), $argument);
        } elseif ($argument instanceof Definition) {
            $object = $this->instantiateDefinition($argument, $builder);
            foreach ($argument->getMethodCalls() as $methodCall) {
                $object->{$methodCall->getMethodName()}(...$this->resolveArgument($methodCall->getArguments(), $builder));
            }
            return $object;
        } elseif ($argument instanceof Reference) {
            return $this->resolveReference($argument, $builder);
        } else {
            if (is_object($argument) && ! ($argument instanceof DefinedObject)) {
                if (! str_starts_with(get_class($argument), 'Test\\')) {
                    // We accept only not-dumpable instances from the 'Test\' namespace
                    throw new InvalidArgumentException('Argument is not a self defined object: ' . get_class($argument));
                }
            }
            return $argument;
        }
    }

    private function instantiateDefinition(Definition $definition, ContainerBuilder $builder): mixed
    {
        if ($definition instanceof DefinedObjectWrapper) {
            return $definition->instance();
        }

        $arguments = $this->resolveArgument($definition->getArguments(), $builder);
        if ($definition->hasFactory()) {
            $factory = $definition->getFactory();
            if (method_exists($factory[0], $factory[1]) && (new ReflectionMethod($factory[0], $factory[1]))->isStatic()) {
                // static call
                return $factory(...$arguments);
            } else {
                // method call from a service instance
                $service = $this->resolveReference(new Reference($factory[0]), $builder);
                return $service->{$factory[1]}(...$arguments);
            }
        } else {
            $class = $definition->getClassName();
            return new $class(...$arguments);
        }
    }

    private function resolveReference(Reference $reference, ContainerBuilder $builder): mixed
    {
        $id = $reference->getId();
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }
        if ($builder->has($id)) {
            $object = $this->resolveArgument($builder->getDefinition($id), $builder);
            $this->container->set($id, $object);

            return $this->container->get($reference->getId());
        }
        if ($this->externalContainer?->has($id)) {
            return $this->externalContainer->get($id);
        }
        if ($this->externalContainer?->has(self::ALIAS_PREFIX . $id)) {
            return $this->externalContainer->get(self::ALIAS_PREFIX . $id);
        }
        if ($reference->getInvalidBehavior() === self::NULL_ON_INVALID_REFERENCE) {
            return null;
        }
        throw new InvalidArgumentException("Reference {$id} was not found in definitions");
    }


}
