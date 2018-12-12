<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\AggregateIdentifier;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerExample
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateQueryHandlerExample
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
     * @QueryHandler(endpointId="some-id")
     */
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}