<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute]
class AsynchronousRunningEndpoint
{
    public function __construct(private string $endpointId)
    {
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}
