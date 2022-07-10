<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Parameter;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Payload
{
    public string $expression = "";

    public function __construct(string $expression = "")
    {
        $this->expression = $expression;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}