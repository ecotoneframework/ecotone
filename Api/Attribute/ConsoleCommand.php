<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class ConsoleCommand
{
    private string $name;
    public readonly string $description;

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
}
