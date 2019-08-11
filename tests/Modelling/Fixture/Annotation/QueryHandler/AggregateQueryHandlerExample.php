<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AggregateQueryHandlerExample
 * @package Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler
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