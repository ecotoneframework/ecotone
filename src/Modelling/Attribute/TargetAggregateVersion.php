<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[TargetVersion] instead
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class TargetAggregateVersion
{
}
