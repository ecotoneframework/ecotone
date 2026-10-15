<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class EventSourcingSaga extends EventSourcingAggregate
{
}
