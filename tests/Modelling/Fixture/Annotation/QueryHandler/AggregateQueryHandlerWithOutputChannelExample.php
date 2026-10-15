<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

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
