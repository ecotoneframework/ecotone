<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
trait MatchesEventSourcedAggregateTypes
{
    public function canHandle(string $aggregateClassName): bool
    {
        if ($this->aggregateTypes === null) {
            return false;
        }

        if (in_array($aggregateClassName, $this->aggregateTypes)) {
            return true;
        }

        $classDefinition = ClassDefinition::createFor(Type::object($aggregateClassName));
        return $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(EventSourcingAggregate::class)) || $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(EventSourcingSaga::class));
    }
}
