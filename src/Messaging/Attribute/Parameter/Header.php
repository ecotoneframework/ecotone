<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Parameter;

use Attribute;
use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class Header implements WithExpression
{
    public string $headerName;
    public string|Closure $expression = '';

    public function __construct(string $headerName, string|Closure $expression = '')
    {
        Assert::notNullAndEmpty($headerName, 'Header name must not be empty string');

        $this->headerName = $headerName;
        $this->expression = $expression;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getExpression(): string|Closure
    {
        return $this->expression;
    }
}
