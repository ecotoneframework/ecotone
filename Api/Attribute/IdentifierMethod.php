<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class IdentifierMethod
{
    public readonly string $identifierPropertyName;

    public function __construct(string $identifierPropertyName)
    {
        Assert::notNullAndEmpty($identifierPropertyName, 'Property name must be defined for ' . self::class);
        $this->identifierPropertyName = $identifierPropertyName;
    }
}
