<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class AggregateQueryHandlerWithOutputChannelExample
{
    #[AggregateIdentifier]
    private string $id;

    #[QueryHandler(endpointId: "some-id", outputChannelName: "outputChannel")]
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}