<?php


namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\DomainModel\Annotation\AggregateEvents;

/**
 * Class WithAggregateEvents
 * @package SimplyCodedSoftware\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
trait WithAggregateEvents
{
    /**
     * @var object[]
     */
    private $recordedEvents = [];

    /**
     * @param object $event
     */
    public function record(object $event) : void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return object[]
     * @AggregateEvents()
     */
    public function getRecordedEvents() : array
    {
        $recordedEvents = $this->recordedEvents;

        $this->recordedEvents = [];
        return $recordedEvents;
    }
}