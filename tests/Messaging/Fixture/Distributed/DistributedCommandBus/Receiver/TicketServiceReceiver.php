<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\EventBus;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\Event\TicketCreated;

/**
 * licence Apache-2.0
 */
class TicketServiceReceiver
{
    public const CREATE_TICKET_ENDPOINT = 'createTicket';
    public const CREATE_TICKET_ENDPOINT_WITH_CONVERSION = 'createTicketWithConversion';
    public const CREATE_TICKET_WITH_EVENT_ENDPOINT = 'createTicketWithEvent';
    public const GET_TICKETS_COUNT      = 'getTicketsCount';
    public const GET_TICKETS      = 'getTickets';

    /**
     * @var Message[]
     */
    private array $tickets = [];

    public function __construct(private array $delays = [])
    {

    }

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_ENDPOINT)]
    public function registerTicket(Message $ticket): void
    {
        $this->tickets[] = $ticket;
    }

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_ENDPOINT_WITH_CONVERSION)]
    public function registerTicketWithConversion(RegisterTicket $command): void
    {
        $this->tickets[] = $command;
    }

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_WITH_EVENT_ENDPOINT)]
    public function registerTicketWithEvent(string $ticket, EventBus $eventBus): void
    {
        $delay = array_shift($this->delays);
        if ($delay) {
            sleep($delay);
        }

        $this->tickets[] = $ticket;

        $eventBus->publish(new TicketCreated($ticket));
    }

    #[QueryHandler(self::GET_TICKETS_COUNT)]
    public function getTicketsCount(): int
    {
        return count($this->tickets);
    }

    #[QueryHandler(self::GET_TICKETS)]
    public function getTickets(): array
    {
        return $this->tickets;
    }
}
