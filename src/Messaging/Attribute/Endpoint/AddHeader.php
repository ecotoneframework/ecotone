<?php

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class AddHeader
{
    private string $headerName;
    private mixed $headerValue;

    public function __construct(string $name, mixed $value = null, private string|null $expression = null)
    {
        Assert::notNullAndEmpty($name, 'Name of the header can not be empty');
        Assert::isTrue(
            ($value === null && $expression !== null)
            || ($value !== null && $expression === null),
            'Either value or expression should be provided for attribute ' . static::class
        );

        $this->headerName  = $name;
        $this->headerValue = $value;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getHeaderValue(): mixed
    {
        return $this->headerValue;
    }

    public function isExpression(): bool
    {
        return false;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }
}
