<?php

namespace Ecotone\Projecting\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Enterprise
 */
class Polling
{
    public function __construct(
        private string $endpointId
    ) {
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}
