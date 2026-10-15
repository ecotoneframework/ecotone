<?php

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
class EventSourcingAggregate extends Aggregate
{
    public const DEFAULT_INTERNAL_EVENT_RECORDER = false;

    public readonly bool $withInternalEventRecorder;

    public function __construct(bool $withInternalEventRecorder = self::DEFAULT_INTERNAL_EVENT_RECORDER)
    {
        $this->withInternalEventRecorder = $withInternalEventRecorder;
    }
}
