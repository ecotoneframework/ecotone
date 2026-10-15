<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\EndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Router extends EndpointAnnotation
{
    public readonly bool $isResolutionRequired;

    public function __construct(string $inputChannelName, string $endpointId = '', bool $isResolutionRequired = true)
    {
        parent::__construct($inputChannelName, $endpointId);
        $this->isResolutionRequired = $isResolutionRequired;
    }
}
