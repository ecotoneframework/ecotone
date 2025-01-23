<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver;

final class RegisterTicket
{
    public function __construct(public string $ticketId)
    {

    }
}
