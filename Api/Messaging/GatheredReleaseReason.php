<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
enum GatheredReleaseReason: string
{
    case MANUAL_RELEASE = 'manual_release';

    case TIMEOUT = 'timeout';

    case ALL_PARTS_ACCOUNTED_FOR = 'all_parts_accounted_for';
}
