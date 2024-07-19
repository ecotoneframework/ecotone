<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class MessageConsumer
{
    private string $endpointId;

    public function __construct(string $endpointId)
    {
        $this->endpointId          = $endpointId;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}
