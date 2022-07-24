<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TargetAggregateIdentifier
{
    public string $identifierName = '';

    public function __construct(string $identifierName = '')
    {
        $this->identifierName = $identifierName;
    }

    public function getIdentifierName(): string
    {
        return $this->identifierName;
    }
}
