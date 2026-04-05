<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

/**
 * licence Enterprise
 */
#[Attribute]
class WithoutMessageCollector implements AsynchronousEndpointAttribute
{
}
