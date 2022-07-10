<?php

namespace Ecotone\EventSourcing;

use Ecotone\Modelling\Event;

interface EventStreamEmitter
{
    /**
     * Link given events to chosen stream.
     *
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function linkTo(string $streamName, array $streamEvents): void;

    /**
     * To be used only in projection.
     * Given events will be linked to stream with same name as projection.
     *
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function emit(array $streamEvents): void;
}