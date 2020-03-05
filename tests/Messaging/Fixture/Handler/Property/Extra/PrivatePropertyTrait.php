<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property\Extra;

use Ecotone\Modelling\Annotation\AggregateIdentifier;

trait PrivatePropertyTrait
{
    /**
     * @var ExtraObject
     * @AggregateIdentifier()
     */
    private $property;
}