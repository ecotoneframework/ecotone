<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateWithNoParametersAndInputChannelAndNoIgnoreMessage
{
    #[Identifier]
    private string $id;

    #[CommandHandler('command', 'endpoint-command')]
    public function doCommand(): void
    {
    }

    #[QueryHandler('query', 'endpoint-query')]
    public function doQuery()
    {
    }
}
