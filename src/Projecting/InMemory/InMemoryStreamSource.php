<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\Modelling\Event;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;

class InMemoryStreamSource implements StreamSource
{
    /**
     * @param Event[] $events
     */
    public function __construct(
        private ?string $partitionHeader = null,
        private array   $events = [],
    ) {
    }

    public function append(Event ...$events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    public function load(?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
    {
        $from = $lastPosition !== null ? (int) $lastPosition : 0;

        if ($partitionKey) {
            $events = array_filter($this->events, fn (Event $event) => $event->getMetadata()[$this->partitionHeader] === $partitionKey);
        } else {
            $events = $this->events;
        }

        $events = array_slice($events, $from, $count);
        $to = $from + count($events);

        return new StreamPage($events, (string) $to);
    }
}
