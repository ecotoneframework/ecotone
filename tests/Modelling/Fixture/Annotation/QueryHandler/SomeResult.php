<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Annotation\TargetAggregateIdentifier;

/**
 * Class SomeResult
 * @package Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler
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