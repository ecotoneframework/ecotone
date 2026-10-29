<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
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
