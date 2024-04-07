<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class IdentifierMethod extends AggregateIdentifierMethod
{
}
