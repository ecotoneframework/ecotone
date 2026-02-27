<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\Parameter\Header;
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
