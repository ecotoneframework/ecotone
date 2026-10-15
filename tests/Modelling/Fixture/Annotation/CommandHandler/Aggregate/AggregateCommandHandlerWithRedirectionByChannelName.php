<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

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
