<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[TargetIdentifier] instead
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * licence Apache-2.0
 */
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
