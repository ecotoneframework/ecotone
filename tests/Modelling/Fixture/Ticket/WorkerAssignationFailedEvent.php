<?php


namespace Ecotone\Tests\Modelling\Fixture\Ticket;


class WorkerAssignationFailedEvent
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
     * AssignationFailed constructor.
     * @param string $ticketId
     * @param string $assignedWorkerId
     */
    public function __construct(string $ticketId, string $assignedWorkerId)
    {
        $this->ticketId = $ticketId;
        $this->assignedWorkerId = $assignedWorkerId;
    }
}