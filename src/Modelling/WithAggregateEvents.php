<?php


namespace Ecotone\Modelling;

use Ecotone\Modelling\Annotation\AggregateEvents;

/**
 * Class WithAggregateEvents
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
trait WithAggregateEvents
{
    private $recordedEvents = null;

    /**
     * @param object $event
     */
    public function record(object $event) : void
    {
        if (!$this->recordedEvents) {
            $this->recordedEvents = [];
        }

        $this->recordedEvents[] = $event;
    }

    /**
     * @return object[]
     * @AggregateEvents()
     */
    public function getRecordedEvents() : array
    {
        if (!$this->recordedEvents) {
            return [];
        }

        $recordedEvents = $this->recordedEvents;
        $this->recordedEvents = null;

        return $recordedEvents;
    }
}