<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
enum FlowChange: string
{
    case ADDED = 'added';
    case REMOVED = 'removed';
    case CHANGED = 'changed';
    case UNCHANGED = 'unchanged';
}
