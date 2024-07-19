<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;

/**
 * licence Apache-2.0
 */
final class ConsoleCommandWithArrayOptions
{
    private array $parameters = [];

    #[ConsoleCommand('cli-command:array-options')]
    public function onlyArrayOfOptions(#[ConsoleParameterOption] array $names): void
    {
        $this->parameters[] = $names;
    }

    #[ConsoleCommand('cli-command:array-options-and-argument')]
    public function withArrayOfOptions(string $email, array $names): void
    {
        $this->parameters[] = $email;
        $this->parameters[] = $names;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
