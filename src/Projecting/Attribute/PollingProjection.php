<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Marks a projection class as a polling projection.
 * Polling projections are triggered by inbound channel adapters instead of event-driven routing.
 * They continuously poll the event store for new events.
 *
 * @example
 * #[PollingProjection('my_projection', endpointId: 'my_projection_poller')]
 * #[FromStream(Ticket::class)]
 * class MyProjection {
 *     #[EventHandler]
 *     public function when(TicketWasRegistered $event): void { ... }
 * }
 *
 * licence Enterprise
 */
#[Attribute]
class PollingProjection extends Projection
{
    public function __construct(
        string  $name,
        public readonly string $endpointId,
    ) {
        parent::__construct($name, null, true);
        $this->runningMode = self::RUNNING_MODE_POLLING;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    public function isPolling(): bool
    {
        return true;
    }
}
