<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\AggregateMessage;

/** @TODO Ecotone 2.0 make only Identifier attribute for Aggregate and Saga */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class SagaIdentifier extends AggregateIdentifier
{

}
