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

    public function getFor(TypeDescriptor $className): AggregateClassDefinition
    {
        if (isset($this->aggregateDefinitions[$className->toString()])) {
            return $this->aggregateDefinitions[$className->toString()];
        }

        throw new ConfigurationException("No aggregate was registered for {$className->toString()}. Is this class name correct, and have you marked this class with #[Aggregate] attribute?");
    }

    public function has(TypeDescriptor $className): bool
    {
        return isset($this->aggregateDefinitions[$className->toString()]);
    }
}
