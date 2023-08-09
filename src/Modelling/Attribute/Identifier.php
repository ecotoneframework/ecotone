<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class Identifier extends AggregateIdentifier
{
}
