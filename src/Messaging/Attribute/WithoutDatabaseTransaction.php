<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

/**
 * licence Apache-2.0
 */
#[Attribute]
class WithoutDatabaseTransaction implements AsynchronousEndpointAttribute
{
}
