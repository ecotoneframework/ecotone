<?php

namespace Ecotone\Modelling;

/**
 * Interface CQRS
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateMessage
{
    const OVERRIDE_AGGREGATE_IDENTIFIER = "aggregate.id";
    const AGGREGATE_OBJECT = "ecotone.modelling.aggregate";
    const AGGREGATE_OBJECT_EXISTS = "ecotone.modelling.aggregate_exists";
    const AGGREGATE_ID     = "ecotone.modelling.aggregate.id";
    const TARGET_VERSION   = "ecotone.modelling.aggregate.target_version";
}