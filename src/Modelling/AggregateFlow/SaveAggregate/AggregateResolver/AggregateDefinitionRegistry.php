<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * licence Apache-2.0
 */
final class AggregateDefinitionRegistry
{
    /**
     * @param AggregateClassDefinition[] $aggregateDefinitions
     */
    public function __construct(
        private array $aggregateDefinitions,
    ) {

    }

    /**
     * @param TypeDescriptor|class-string $className
     */
    public function getFor(TypeDescriptor|string $className): AggregateClassDefinition
    {
        if (isset($this->aggregateDefinitions[(string) $className])) {
            return $this->aggregateDefinitions[(string) $className];
        }

        throw new ConfigurationException("No aggregate was registered for {$className}. Is this class name correct, and have you marked this class with #[Aggregate] attribute?");
    }

    /**
     * @param TypeDescriptor|class-string $className
     */
    public function has(TypeDescriptor|string $className): bool
    {
        return isset($this->aggregateDefinitions[(string) $className]);
    }
}
