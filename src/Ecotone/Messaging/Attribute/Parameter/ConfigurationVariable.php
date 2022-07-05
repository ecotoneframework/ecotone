<?php


namespace Ecotone\Messaging\Attribute\Parameter;

#[\Attribute]
class ConfigurationVariable
{
    private string $name;

    public function __construct(string $name = "")
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}