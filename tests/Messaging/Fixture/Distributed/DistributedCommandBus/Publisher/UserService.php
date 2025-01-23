<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\DistributedBus;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver\TicketServiceReceiver;
use Test\Ecotone\Messaging\Fixture\Distributed\TestServiceName;

/**
 * licence Apache-2.0
 */
class UserService
{
    public const CHANGE_BILLING_DETAILS = 'changeBillingDetails';

    #[CommandHandler(self::CHANGE_BILLING_DETAILS)]
    public function changeBillingDetails(#[Reference] DistributedBus $distributedBus)
    {
        $distributedBus->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );
    }
}
