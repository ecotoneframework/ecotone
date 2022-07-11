<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Parameter;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Reference
{
    public string $referenceName;

    public function __construct(string $referenceName = '')
    {
        $this->referenceName = $referenceName;
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}
