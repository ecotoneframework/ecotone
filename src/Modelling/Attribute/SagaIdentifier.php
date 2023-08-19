<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[Identifier] instead
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SagaIdentifier extends AggregateIdentifier
{
}
