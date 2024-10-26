<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface TicketCreator
{
    #[MessageGateway('create')]
    public function create($data);
}
