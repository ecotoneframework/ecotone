<?php

namespace Ecotone\EventSourcing\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Projecting\ProjectingHeaders;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
final class ProjectionState extends Header
{
    public function __construct()
    {
        parent::__construct(ProjectingHeaders::PROJECTION_STATE);
    }
}
