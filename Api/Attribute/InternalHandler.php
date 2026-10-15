<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
/**
 * licence Apache-2.0
 */
class InternalHandler extends InputOutputEndpointAnnotation
{
    public function __construct(
        string $inputChannelName,
        string $outputChannelName = '',
        string $endpointId = '',
        array $requiredInterceptorNames = [],
        public readonly bool $changingHeaders = false,
        public readonly bool $requiresReply = false,
    ) {
        parent::__construct($inputChannelName, $endpointId, $outputChannelName, $requiredInterceptorNames);
    }
}
