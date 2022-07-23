<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Transformer extends InputOutputEndpointAnnotation
{
}
