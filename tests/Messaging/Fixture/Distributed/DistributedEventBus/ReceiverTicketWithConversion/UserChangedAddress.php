<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion;

final class UserChangedAddress
{
    public function __construct(public string $userId)
    {

    }
}
