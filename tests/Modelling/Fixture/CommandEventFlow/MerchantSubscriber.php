<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

final class MerchantSubscriber
{
    #[EventHandler]
    public function merchantToUser(MerchantCreated $event, CommandBus $commandBus): void
    {
        $commandBus->send(new RegisterUser($event->merchantId));
    }
}