<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[IdentifierMethod] instead
 */
#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class AggregateIdentifierMethod
{
    private string $identifierPropertyName;

    public function __construct(string $identifierPropertyName)
    {
        Assert::notNullAndEmpty($identifierPropertyName, 'Property name must be defined for ' . self::class);
        $this->identifierPropertyName = $identifierPropertyName;
    }

    public function getIdentifierPropertyName(): string
    {
        return $this->identifierPropertyName;
    }
}
