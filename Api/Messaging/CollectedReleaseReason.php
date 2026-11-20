<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
enum CollectedReleaseReason: string
{
    case MANUAL_RELEASE = 'manual_release';
    case MAX_WAIT_REACHED = 'max_wait_reached';
    case MAX_SIZE_REACHED = 'max_size_reached';
}
