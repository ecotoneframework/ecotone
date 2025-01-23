<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\EventBus;
use RuntimeException;

/**
 * licence Apache-2.0
 */
class TicketServiceReceiver
{
    public const GET_TICKETS_COUNT      = 'getTicketsCount';
    public const GET_TICKETS      = 'getTickets';

    private array $tickets = [];

    #[Distributed]
    #[EventHandler('userService.*')]
    public function registerTicket(
        Message $message,
        EventBus $eventBus,
        #[Header('shouldThrowException')] bool $shouldThrowException = false,
    ): void {
        $this->tickets[] = $message;
        $eventBus->publish(new TicketRegistered($message->getPayload()));

        if ($shouldThrowException) {
            throw new RuntimeException('Should throw exception');
        }
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
