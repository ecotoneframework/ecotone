<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

/**
 * licence Enterprise
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Enterprise
{
}
