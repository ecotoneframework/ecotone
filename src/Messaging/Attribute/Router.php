<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Router extends EndpointAnnotation
{
    private bool $isResolutionRequired;

    public function __construct(string $inputChannelName, string $endpointId = '', bool $isResolutionRequired = true)
    {
        parent::__construct($inputChannelName, $endpointId);
        $this->isResolutionRequired = $isResolutionRequired;
    }

    public function isResolutionRequired(): bool
    {
        return $this->isResolutionRequired;
    }
}
