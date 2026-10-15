<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedMessage\Publisher;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Gateway\DistributedBus;
use Test\Ecotone\Amqp\Fixture\DistributedMessage\Receiver\TicketServiceMessagingConfiguration;
use Test\Ecotone\Amqp\Fixture\DistributedMessage\Receiver\TicketServiceReceiver;

/**
 * licence Apache-2.0
 */
class UserService
{
    public const CHANGE_BILLING_DETAILS = 'changeBillingDetails';

    #[CommandHandler(self::CHANGE_BILLING_DETAILS)]
    public function changeBillingDetails(#[Reference] DistributedBus $distributedBus)
    {
        $distributedBus->sendMessage(
            TicketServiceMessagingConfiguration::SERVICE_NAME,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address'
        );
    }
}
