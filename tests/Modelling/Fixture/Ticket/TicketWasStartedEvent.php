<?php

namespace Test\Ecotone\Modelling\Fixture\Ticket;

/**
 * licence Apache-2.0
 */
class TicketWasStartedEvent
{
    /**
     * @var string
     */
    private $ticketId;

    /**
     * TicketWasStarted constructor.
     * @param string $ticketId
     */
    public function __construct(string $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }
}
