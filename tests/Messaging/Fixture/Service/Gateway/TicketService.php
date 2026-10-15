<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
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
