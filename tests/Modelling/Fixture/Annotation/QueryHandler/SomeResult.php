<?php

namespace Ecotone\Tests\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class SomeResult
 * @package Ecotone\Tests\Modelling\Fixture\Annotation\QueryHandler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SomeResult
{
    #[TargetAggregateIdentifier]
    private $aggregateId;
}