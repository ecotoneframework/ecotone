<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use RuntimeException;

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
    public function createTicketViaCommand(mixed $data, #[Header('throwException')] bool $throwException = false): void
    {
        if ($throwException) {
            throw new RuntimeException('test');
        }

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
