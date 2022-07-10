<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes;

#[\Attribute]
class AddMetadata
{
    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}