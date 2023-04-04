<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

/** @TODO Ecotone 2.0 make only Identifier attribute for Aggregate and Saga */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class SagaIdentifier extends AggregateIdentifier
{
}
