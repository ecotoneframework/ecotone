<?php

namespace Ecotone\Modelling;

/**
 * Interface CQRS
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface AggregateMessage
{
    public const OVERRIDE_AGGREGATE_IDENTIFIER = 'aggregate.id';
    public const AGGREGATE_OBJECT_EXISTS = 'ecotone.modelling.aggregate_exists';
    public const CALLED_AGGREGATE_INSTANCE = 'ecotone.modelling.called_aggregate';
    public const CALLED_AGGREGATE_CLASS = 'ecotone.modelling.called_aggregate_class';
    public const AGGREGATE_ID = 'ecotone.modelling.aggregate.id';
    public const TARGET_VERSION = 'ecotone.modelling.aggregate.target_version';
    public const RECORDED_AGGREGATE_EVENTS = 'ecotone.modelling.called_aggregate_events';
    public const NULL_EXECUTION_RESULT = 'ecotone.modelling.is_nullable_execution_result';
}
