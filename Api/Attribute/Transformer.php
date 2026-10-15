<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Transformer extends InputOutputEndpointAnnotation
{
}
