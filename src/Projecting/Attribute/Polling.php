<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Marks a projection as polling-based.
 * When combined with ProjectionV2, the projection will be triggered by polling instead of event-driven.
 * The endpointId is used to identify the polling endpoint.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Polling
{
    public function __construct(
        public readonly string $endpointId,
    ) {
    }
}
