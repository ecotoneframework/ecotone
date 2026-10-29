<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

/**
 * licence Apache-2.0
 */
enum FieldType: int
{
    case METADATA = 0;

    case MESSAGE_PROPERTY = 1;
}
