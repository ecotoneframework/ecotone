<?php

namespace Ecotone\Messaging\Attribute;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ClassReference
{
    private string $referenceName;

    public function __construct(string $referenceName)
    {
        Assert::notNullAndEmpty($referenceName, "Reference name can not be empty string");
        $this->referenceName = $referenceName;
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}