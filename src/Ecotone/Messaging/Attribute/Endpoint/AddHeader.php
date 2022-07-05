<?php


namespace Ecotone\Messaging\Attribute\Endpoint;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AddHeader
{
    private string $headerName;
    private mixed $headerValue;

    public function __construct(string $name, mixed $value)
    {
        Assert::notNullAndEmpty($name, "Name of the header can not be empty");

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
}