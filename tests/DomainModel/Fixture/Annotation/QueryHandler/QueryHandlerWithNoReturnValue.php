<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;

/**
 * Class QueryHandlerWithNoReturnValue
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler
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