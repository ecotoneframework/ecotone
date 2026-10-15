<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Gateway\CommandBus;

/**
 * licence Apache-2.0
 */
final class MerchantSubscriber
{
    #[InternalHandler('merchantToUser')]
    #[EventHandler]
    public function merchantToUser(MerchantCreated $event, CommandBus $commandBus): MerchantCreated
    {
        $commandBus->send(new RegisterUser($event->merchantId));

        return $event;
    }
}
