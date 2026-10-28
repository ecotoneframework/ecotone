<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class ProjectingHeaders
{
    public const PROJECTION_STATE = 'ecotone_projection_state';
    public const PROJECTION_NAME = 'ecotone_projection_name';
    public const PROJECTION_EVENT_NAME = 'ecotone_projection_event_name';
    /**
     * Indicates whether the projection is live and should emit events.
     * When false, events emitted via EventStreamEmitter will be filtered out.
     */
    public const PROJECTION_LIVE = 'ecotone_projection_live';
    public const MANUAL_INITIALIZATION = 'ecotone_projection_manual_initialization';
    public const PROJECTION_PARTITION_KEY = 'ecotone_projection_partition_key';
    public const PROJECTION_CAN_INITIALIZE = 'ecotone_projection_can_initialize';
    public const PROJECTION_SHOULD_RESET = 'ecotone_projection_should_reset';
    public const REBUILD_BATCH_NUMBER = 'ecotone_projecting_rebuild_batch_number';
    public const REBUILD_BATCH_COUNT = 'ecotone_projecting_rebuild_batch_count';
    public const REBUILD_PARTITION_COUNT = 'ecotone_projecting_rebuild_partition_count';
    public const REBUILD_PARTITION_KEY = 'projection.rebuild.partitionKey';
}
