<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithRedirectionByChannelName
{
    #[Identifier]
    private string $id;

    #[CommandHandler('sameChannel', 'factory')]
    public static function factory(): void
    {
    }

    #[CommandHandler('sameChannel', 'action')]
    public function action(): void
    {
    }
}
