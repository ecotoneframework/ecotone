<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Splitter extends InputOutputEndpointAnnotation
{
    public function __construct(
        string $inputChannelName = '',
        string $endpointId = '',
        string $outputChannelName = '',
        array $requiredInterceptorNames = [],
        public readonly string $gatherChannelName = '',
    ) {
        parent::__construct($inputChannelName, $endpointId, $outputChannelName, $requiredInterceptorNames);
    }
}
