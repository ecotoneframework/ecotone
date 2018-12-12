<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\AggregateIdentifier;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerWithOutputChannelExample
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler
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