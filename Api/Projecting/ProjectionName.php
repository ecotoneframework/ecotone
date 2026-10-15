<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Api\Attribute\Header;
use Ecotone\Projecting\ProjectingHeaders;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ProjectionName extends Header
{
    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return ProjectingHeaders::PROJECTION_NAME;
    }
}
