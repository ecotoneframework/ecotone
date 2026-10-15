<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Gateway\CommandBus;

final class MerchantSubscriberWithMetadata
{
    #[InternalHandler('merchantToUser')]
    #[EventHandler]
    public function merchantToUser(MerchantCreated $event, array $metadata, CommandBus $commandBus): MerchantCreated
    {
        $commandBus->send(new RegisterUser($event->merchantId), $metadata);

        return $event;
    }
}
