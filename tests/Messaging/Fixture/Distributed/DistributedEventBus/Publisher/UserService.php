<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\Publisher;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\DistributedBus;

/**
 * licence Apache-2.0
 */
class UserService
{
    public const CHANGE_BILLING_DETAILS = 'changeBillingDetails';
    public const BILLING_DETAILS_WERE_CHANGED = 'userService.billing.DetailsWereChanged';

    #[CommandHandler(self::CHANGE_BILLING_DETAILS)]
    public function changeBillingDetails(
        #[Reference] DistributedBus $distributedBus,
        #[Header('shouldThrowException')] bool $shouldThrowException = false,
    ) {
        $distributedBus->publishEvent(
            self::BILLING_DETAILS_WERE_CHANGED,
            'ticket was created',
            metadata: [
                'shouldThrowException' => $shouldThrowException,
            ]
        );
    }
}
