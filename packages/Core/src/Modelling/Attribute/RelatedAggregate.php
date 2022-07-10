<?php

namespace Ecotone\Modelling\Attribute;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RelatedAggregate
{
    private string $className;

    public function __construct(string $className)
    {
        Assert::notNullAndEmpty($className, "Class name for aggregate should not be empty.");

        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}