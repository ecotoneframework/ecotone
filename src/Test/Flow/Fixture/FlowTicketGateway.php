<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface FlowTicketGateway
{
    #[Asynchronous('tickets')]
    #[MessageGateway('ticket.create')]
    public function create(string $ticket): void;
}
