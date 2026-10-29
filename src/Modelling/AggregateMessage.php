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
    public const CALLED_AGGREGATE_INSTANCE = 'ecotone.modelling.called_aggregate';
    public const CALLED_AGGREGATE_CLASS = 'ecotone.modelling.called_aggregate_class';
    public const TARGET_VERSION = 'ecotone.modelling.aggregate.target_version';
    public const RECORDED_AGGREGATE_EVENTS = 'ecotone.modelling.called_aggregate_events';
    public const NULL_EXECUTION_RESULT = 'ecotone.modelling.is_nullable_execution_result';

    // test setup state headers
    public const TEST_SETUP_AGGREGATE_VERSION = 'ecotone.modeling.test_setup.aggregate_version';
    public const TEST_SETUP_AGGREGATE_CLASS = 'ecotone.modeling.test_setup.aggregate_class';
    public const TEST_SETUP_AGGREGATE_INSTANCE = 'ecotone.modeling.test_setup.aggregate_instance';
    public const TEST_SETUP_AGGREGATE_EVENTS = 'ecotone.modeling.test_setup.aggregate_events';
}
