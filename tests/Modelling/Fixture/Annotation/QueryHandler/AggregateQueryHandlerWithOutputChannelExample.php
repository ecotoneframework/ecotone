<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateQueryHandlerWithOutputChannelExample
{
    #[Identifier]
    private string $id;

    #[QueryHandler(endpointId: 'some-id', outputChannelName: 'outputChannel')]
    public function doStuff(SomeQuery $query): SomeResult
    {
        return new SomeResult();
    }
}
