<?php

namespace Ecotone\Messaging\Attribute\Parameter;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class ConfigurationVariable
{
    private string $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
