<?php

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class ConsoleCommand
{
    private string $name;
    private string $description;

    public function __construct(string $consoleCommandName, string $description = '')
    {
        Assert::notNullAndEmpty($consoleCommandName, 'Console command name can not be empty string');
        $this->name = $consoleCommandName;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
