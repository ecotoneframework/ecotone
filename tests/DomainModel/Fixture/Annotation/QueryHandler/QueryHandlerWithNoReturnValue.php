<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\QueryHandler;

/**
 * Class QueryHandlerWithNoReturnValue
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class QueryHandlerWithNoReturnValue
{
    /**
     * @param SomeQuery $query
     * @return void
     * @QueryHandler()
     */
    public function searchFor(SomeQuery $query) : void
    {
        return;
    }
}