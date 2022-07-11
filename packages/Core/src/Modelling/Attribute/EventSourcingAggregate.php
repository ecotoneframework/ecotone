<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class EventSourcingAggregate extends Aggregate
{
    public const DEFAULT_INTERNAL_EVENT_RECORDER = false;

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
