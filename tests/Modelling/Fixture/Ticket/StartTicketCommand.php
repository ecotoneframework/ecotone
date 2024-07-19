<?php

namespace Test\Ecotone\Modelling\Fixture\Ticket;

/**
 * licence Apache-2.0
 */
class StartTicketCommand
{
    /**
     * @var string
     */
    private $ticketId;

    /**
     * StartTicketCommand constructor.
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
