<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Parameter;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class Header
{
    public string $headerName;
    public string $expression = '';

    public function __construct(string $headerName, string $expression = '')
    {
        Assert::notNullAndEmpty($headerName, 'Header name must not be empty string');

        $this->headerName = $headerName;
        $this->expression = $expression;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}
