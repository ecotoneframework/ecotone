<?php

namespace Ecotone\Messaging\Config\Container;

class ConsoleCommandReference extends Reference
{
    public function __construct(private string $commandName)
    {
        parent::__construct("console.$commandName");
    }
}
