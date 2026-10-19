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
    /** User facing header, may contain scalar and array format  */
    public const OVERRIDE_AGGREGATE_IDENTIFIER = 'aggregate.id';
    /** Internal header, format is always array (identityName: value). Should be used via AggregateIdMetadata */
    public const AGGREGATE_ID = 'ecotone.modelling.aggregate.id';
    public const CALLED_AGGREGATE_INSTANCE = 'ecotone_modelling_aggregate_called_instance';
    public const CALLED_AGGREGATE_CLASS = 'ecotone_modelling_aggregate_called_class';
    public const CALLED_AGGREGATE_COUNTER_CAPTURED_AT_LOAD = 'ecotone_modelling_aggregate_counter_captured_at_load';
    public const TARGET_VERSION = 'ecotone_modelling_aggregate_target_version';
    public const RECORDED_AGGREGATE_EVENTS = 'ecotone_modelling_aggregate_recorded_events';
    public const NULL_EXECUTION_RESULT = 'ecotone_modelling_aggregate_null_execution_result';

    // test setup state headers
    public const TEST_SETUP_AGGREGATE_VERSION = 'ecotone_modelling_test_setup_aggregate_version';
    public const TEST_SETUP_AGGREGATE_CLASS = 'ecotone_modelling_test_setup_aggregate_class';
    public const TEST_SETUP_AGGREGATE_INSTANCE = 'ecotone_modelling_test_setup_aggregate_instance';
    public const TEST_SETUP_AGGREGATE_EVENTS = 'ecotone_modelling_test_setup_aggregate_events';
}
