<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class SomeResult
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler
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