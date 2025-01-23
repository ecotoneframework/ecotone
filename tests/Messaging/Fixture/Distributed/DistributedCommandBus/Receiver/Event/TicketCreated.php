<?php

declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\Event;

/**
 * licence Apache-2.0
 */
final class TicketCreated
{
    public function __construct(private string $ticket)
    {
    }

    public function getTicket(): string
    {
        return $this->ticket;
    }
}
