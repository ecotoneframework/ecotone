<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class ProjectionPartitionState
{
    public function __construct(
        public readonly string                          $projectionName,
        public readonly ?string                         $partitionKey,
        public readonly ?string                         $lastPosition = null,
        public readonly mixed                           $userState = null,
        public readonly ?ProjectionInitializationStatus $status = null,
    ) {
    }

    public function withLastPosition(string $lastPosition): self
    {
        return new self($this->projectionName, $this->partitionKey, $lastPosition, $this->userState, $this->status);
    }

    public function withUserState(mixed $userState): self
    {
        return new self($this->projectionName, $this->partitionKey, $this->lastPosition, $userState, $this->status);
    }

    public function withStatus(ProjectionInitializationStatus $status): self
    {
        return new self($this->projectionName, $this->partitionKey, $this->lastPosition, $this->userState, $status);
    }
}
