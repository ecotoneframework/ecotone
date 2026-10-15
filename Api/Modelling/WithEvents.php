<?php

namespace Ecotone\Api\Modelling;

use Ecotone\Api\Attribute\AggregateEvents;

/**
 * licence Apache-2.0
 */
trait WithEvents
{
    private array $recordedEvents;

    public function recordThat(object $event): void
    {
        $this->recordedEvents ??= [];
        $this->recordedEvents[] = $event;
    }

    #[AggregateEvents]
    public function popRecordedEvents(): array
    {
        $recordedEvents = $this->recordedEvents ?? [];
        unset($this->recordedEvents);

        return $recordedEvents;
    }
}
