<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class SomeResult
 * @package Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SomeResult
{
    #[TargetAggregateIdentifier]
    private $aggregateId;
}
