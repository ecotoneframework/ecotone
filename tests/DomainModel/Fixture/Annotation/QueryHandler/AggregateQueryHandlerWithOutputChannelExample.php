<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerWithOutputChannelExample
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler
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

    /**
     * @param SomeQuery $query
     *
     * @return SomeResult
     * @QueryHandler(
     *     endpointId="some-id",
     *     outputChannelName="outputChannel"
     * )
     */
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}