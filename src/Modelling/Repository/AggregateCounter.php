<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Api\EventSourcing\AppendCondition;

/**
 * licence Apache-2.0
 */
interface AggregateCounter
{
    /**
     * @param array<string, mixed> $identifiers
     */
    public function captureFor(string $aggregateClassName, array $identifiers): AppendCondition;

    /**
     * @param array<string, mixed> $identifiers
     */
    public function bumpGuarded(string $aggregateClassName, array $identifiers, AppendCondition $capturedAtLoad): void;
}
