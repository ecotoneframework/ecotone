<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler;

use Ecotone\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class SomeResult
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SomeResult
{
    /**
     * @var string
     * @TargetAggregateIdentifier()
     */
    private $aggregateId;
}