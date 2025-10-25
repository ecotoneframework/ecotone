<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Countable;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\ProjectorExecutor;

class InMemoryProjector implements ProjectorExecutor, Countable
{
    private array $projectedEvents = [];

    public function project(Event $event, mixed $userState = null): mixed
    {
        $this->projectedEvents[] = $event;
        return $userState;
    }

    public function getProjectedEvents(): array
    {
        return $this->projectedEvents;
    }

    public function count(): int
    {
        return count($this->projectedEvents);
    }

    public function init(): void
    {
    }

    public function delete(): void
    {
        $this->projectedEvents = [];
    }

    public function flush(): void
    {
    }
}
