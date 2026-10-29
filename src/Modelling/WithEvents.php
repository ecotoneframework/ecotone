<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Attribute\AggregateEvents;

/**
 * Class WithAggregateEvents
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
trait WithEvents
{
    private ?array $recordedEvents = null;

    public function recordThat(object $event): void
    {
        if (! $this->recordedEvents) {
            $this->recordedEvents = [];
        }

        $this->recordedEvents[] = $event;
    }

    #[AggregateEvents]
    public function getRecordedEvents(): array
    {
        if (! $this->recordedEvents) {
            return [];
        }

        $recordedEvents = $this->recordedEvents;
        $this->recordedEvents = null;

        return $recordedEvents;
    }
}
