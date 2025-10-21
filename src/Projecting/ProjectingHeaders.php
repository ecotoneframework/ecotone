<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class ProjectingHeaders
{
    public const PROJECTION_STATE = 'projection.state';
    public const PROJECTION_NAME = 'projection.name';
    public const PROJECTION_EVENT_NAME = 'projection.event_name';
    public const PROJECTION_IS_REBUILDING = 'projection.is_rebuilding';
    public const MANUAL_INITIALIZATION = 'projection.manual_initialization';
}
