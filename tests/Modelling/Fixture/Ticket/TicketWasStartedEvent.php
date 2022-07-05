<?php


namespace Ecotone\Tests\Modelling\Fixture\Ticket;


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