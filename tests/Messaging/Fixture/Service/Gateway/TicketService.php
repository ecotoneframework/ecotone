<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class TicketService
{
    private array $tickets = [];

    #[InternalHandler('create')]
    public function createTicket(mixed $data): void
    {
        $this->tickets[] = $data;
    }

    #[CommandHandler('createViaCommand')]
    public function createTicketViaCommand(mixed $data): void
    {
        $this->tickets[] = $data;
    }

    #[InternalHandler('proxy')]
    public function proxy(mixed $data, AsyncTicketCreator $asyncTicketCreator): void
    {
        $asyncTicketCreator->create($data);
    }

    #[QueryHandler('getTickets')]
    public function getTickets(): array
    {
        return $this->tickets;
    }
}
