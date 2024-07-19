<?php

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class RemoveHeader
{
    private string $headerName;

    public function __construct(string $name)
    {
        Assert::notNullAndEmpty($name, 'Name of the header can not be empty');

        $this->headerName = $name;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }
}
