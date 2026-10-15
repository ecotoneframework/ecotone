<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class RelatedAggregate
{
    public readonly string $className;

    public function __construct(string $className)
    {
        Assert::notNullAndEmpty($className, 'Class name for aggregate should not be empty.');

        $this->className = $className;
    }
}
