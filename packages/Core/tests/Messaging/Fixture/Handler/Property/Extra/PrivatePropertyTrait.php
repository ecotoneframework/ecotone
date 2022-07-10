<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property\Extra;

use Ecotone\Modelling\Attribute\AggregateIdentifier;

trait PrivatePropertyTrait
{
    #[AggregateIdentifier]
    private ?ExtraObject $property;
}