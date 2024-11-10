<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Attribute\AggregateEvents;

/**
 * Class WithAggregateEvents
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @deprecated Use Ecotone\Modelling\WithEvents instead
 * @TODO Ecotone 2.0 replace with WithEvents
 */
/**
 * licence Apache-2.0
 */
trait WithAggregateEvents
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
