<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\EventBus;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Merchant
{
    #[Identifier]
    private string $merchantId;

    #[CommandHandler]
    public static function create(CreateMerchant $command, EventBus $eventBus): self
    {
        $self = new self();
        $self->merchantId = $command->merchantId;

        $eventBus->publish(new MerchantCreated($command->merchantId));

        return $self;
    }
}
