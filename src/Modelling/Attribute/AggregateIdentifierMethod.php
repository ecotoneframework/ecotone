<?php

namespace Ecotone\Modelling\Attribute;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class AggregateIdentifierMethod
{
    private string $identifierPropertyName;

    public function __construct(string $identifierPropertyName)
    {
        Assert::notNullAndEmpty($identifierPropertyName, "Property name must be defined for " . self::class);
        $this->identifierPropertyName = $identifierPropertyName;
    }

    public function getIdentifierPropertyName(): string
    {
        return $this->identifierPropertyName;
    }
}