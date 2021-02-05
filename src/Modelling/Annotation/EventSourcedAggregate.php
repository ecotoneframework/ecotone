<?php


namespace Ecotone\Modelling\Annotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class EventSourcedAggregate extends Aggregate
{
    const DEFAULT_INTERNAL_EVENT_RECORDER = false;

    private bool $withInternalEventRecorder;

    public function __construct(bool $withInternalEventRecorder = self::DEFAULT_INTERNAL_EVENT_RECORDER)
    {
        $this->withInternalEventRecorder = $withInternalEventRecorder;
    }

    public function hasInternalEventRecorder(): bool
    {
        return $this->withInternalEventRecorder;
    }
}