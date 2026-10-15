<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Api\EventSourcing\AppendCondition;

/**
 * licence Apache-2.0
 */
final class OpenCoreAggregateCounter implements AggregateCounter
{
    public function captureFor(string $aggregateClassName, array $identifiers): AppendCondition
    {
        return AppendCondition::empty();
    }

    public function bumpGuarded(string $aggregateClassName, array $identifiers, AppendCondition $capturedAtLoad): void
    {
    }
}
