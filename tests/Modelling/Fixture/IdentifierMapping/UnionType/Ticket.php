<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\UnionType;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Ticket
{
    #[Identifier]
    private InternalId|ExternalId $ticketId;

    private bool $closed = false;

    #[CommandHandler]
    public static function create(CreateTicket $command): self
    {
        $instance = new self();
        $instance->ticketId = $command->ticketId;

        return $instance;
    }

    #[Asynchronous('async')]
    #[CommandHandler(endpointId: 'ticket.createAsync')]
    public static function createAsync(CreateTicketAsync $command): self
    {
        $instance = new self();
        $instance->ticketId = $command->ticketId;

        return $instance;
    }

    #[CommandHandler]
    public function close(CloseTicket $command): void
    {
        $this->closed = true;
    }

    #[CommandHandler(routingKey: 'ticket.closeByMetadataOverride')]
    public function closeByMetadataOverride(): void
    {
        $this->closed = true;
    }

    #[CommandHandler]
    public function closeByTargetIdentifier(CloseTicketByTargetIdentifier $command): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
