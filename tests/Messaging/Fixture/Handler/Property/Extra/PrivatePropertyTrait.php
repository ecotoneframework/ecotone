<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property\Extra;

use Ecotone\Api\Attribute\Identifier;

trait PrivatePropertyTrait
{
    #[Identifier]
    private ?ExtraObject $property;
}
