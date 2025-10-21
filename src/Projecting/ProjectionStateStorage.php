<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

interface ProjectionStateStorage
{
    public function loadPartition(string $projectionName, ?string $partitionKey = null, bool $lock = true): ?ProjectionPartitionState;
    public function initPartition(string $projectionName, ?string $partitionKey = null): ?ProjectionPartitionState; // Returns created state or null if already exists
    public function savePartition(ProjectionPartitionState $projectionState): void;
    public function delete(string $projectionName): void;
    public function init(string $projectionName): void;
    public function beginTransaction(): Transaction;
}
