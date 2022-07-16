<?php

namespace Ecotone\EventSourcing\Attribute;

use Attribute;
use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionExecutor;
use Ecotone\Modelling\Attribute\AggregateIdentifier;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ProjectionState extends AggregateIdentifier
{
    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return ProjectionExecutor::PROJECTION_STATE;
    }
}
