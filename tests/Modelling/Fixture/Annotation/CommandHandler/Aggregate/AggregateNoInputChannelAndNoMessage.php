<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\IgnorePayload;

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
