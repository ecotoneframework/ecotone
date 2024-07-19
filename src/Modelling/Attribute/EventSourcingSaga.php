<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class EventSourcingSaga extends EventSourcingAggregate
{
}
