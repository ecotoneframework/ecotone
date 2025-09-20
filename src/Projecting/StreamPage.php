<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Modelling\Event;

class StreamPage
{
    /**
     * @param array<Event> $events
     */
    public function __construct(
        public readonly array $events,
        public readonly string $lastPosition,
    ) {
    }
}
