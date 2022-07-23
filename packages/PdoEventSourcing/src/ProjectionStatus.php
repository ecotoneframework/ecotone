<?php

namespace Ecotone\EventSourcing;

enum ProjectionStatus
{
    case RUNNING;
    case DELETING;
    case REBUILDING;
}
