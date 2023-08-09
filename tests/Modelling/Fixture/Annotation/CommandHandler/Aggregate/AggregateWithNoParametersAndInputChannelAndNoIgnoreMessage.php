<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
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
