<?php

namespace Ecotone\Modelling;

/**
 * Interface CQRS
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateMessage
{
    public const OVERRIDE_AGGREGATE_IDENTIFIER = 'aggregate.id';
    public const AGGREGATE_OBJECT = 'ecotone.modelling.aggregate';
    public const AGGREGATE_OBJECT_EXISTS = 'ecotone.modelling.aggregate_exists';
    public const AGGREGATE_ID     = 'ecotone.modelling.aggregate.id';
    public const TARGET_VERSION   = 'ecotone.modelling.aggregate.target_version';
}
