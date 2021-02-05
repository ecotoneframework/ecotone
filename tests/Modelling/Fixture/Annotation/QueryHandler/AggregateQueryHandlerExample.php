<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class AggregateQueryHandlerExample
{
    #[AggregateIdentifier]
    private string $id;

    #[QueryHandler(endpointId: "some-id")]
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }

    public function doAnotherAction(SomeQuery $query) : SomeResult
    {

    }
}