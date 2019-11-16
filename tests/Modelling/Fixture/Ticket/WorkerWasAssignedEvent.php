<?php


namespace Test\Ecotone\Modelling\Fixture\Ticket;


class WorkerWasAssignedEvent
{
    /**
     * @var string
     */
    private $ticketId;
    /**
     * @var string
     */
    private $assignedWorkerId;

    /**
     * WorkerWasAssigned constructor.
     * @param string $ticketId
     * @param string $assignedWorkerId
     */
    public function __construct(string $ticketId, string $assignedWorkerId)
    {
        $this->ticketId = $ticketId;
        $this->assignedWorkerId = $assignedWorkerId;
    }

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    /**
     * @return string
     */
    public function getAssignedWorkerId(): string
    {
        return $this->assignedWorkerId;
    }
}