<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerExample
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler
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

    public function doAnotherAction(SomeQuery $query) : SomeResult
    {

    }
}