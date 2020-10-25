<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerWithOutputChannelExample
 * @package Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateQueryHandlerWithOutputChannelExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[QueryHandler(endpointId: "some-id", outputChannelName: "outputChannel")]
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}