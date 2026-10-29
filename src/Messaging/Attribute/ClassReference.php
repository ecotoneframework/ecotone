<?php

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
class ClassReference
{
    private string $referenceName;

    public function __construct(string $referenceName)
    {
        Assert::notNullAndEmpty($referenceName, 'Reference name can not be empty string');
        $this->referenceName = $referenceName;
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}
