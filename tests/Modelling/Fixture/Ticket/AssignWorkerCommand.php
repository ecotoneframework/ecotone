<?php


namespace Test\Ecotone\Modelling\Fixture\Ticket;


class AssignWorkerCommand
{
    /**
     * @var string
     */
    private $ticketId;
    /**
     * @var string
     */
    private $workerId;

    /**
     * AssignWorkerCommand constructor.
     * @param string $ticketId
     * @param string $workerId
     */
    public function __construct(string $ticketId, string $workerId)
    {
        $this->ticketId = $ticketId;
        $this->workerId = $workerId;
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
    public function getWorkerId(): string
    {
        return $this->workerId;
    }
}