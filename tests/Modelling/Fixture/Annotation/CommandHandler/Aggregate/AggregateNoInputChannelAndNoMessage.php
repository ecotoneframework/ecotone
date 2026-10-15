<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\IgnorePayload;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateNoInputChannelAndNoMessage
{
    #[Identifier]
    private string $id;

    #[CommandHandler]
    #[IgnorePayload]
    public function doAction(): void
    {
    }
}
