<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Console;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsoleCommandDescriptionTest extends TestCase
{
    public function test_console_command_attribute_carries_description(): void
    {
        $consoleCommand = new ConsoleCommand('app:import', 'Imports orders from given file');

        $this->assertSame('Imports orders from given file', $consoleCommand->getDescription());
    }

    public function test_console_command_attribute_defaults_to_empty_description(): void
    {
        $consoleCommand = new ConsoleCommand('app:import');

        $this->assertSame('', $consoleCommand->getDescription());
    }

    public function test_configuration_exposes_description_after_container_serialization(): void
    {
        $configuration = ConsoleCommandConfiguration::create('channel', 'app:import', [], 'Imports orders from given file');

        $this->assertSame('Imports orders from given file', $configuration->getDescription());
        $this->assertContains('Imports orders from given file', $configuration->getDefinition()->getArguments());
    }

    public function test_configuration_defaults_to_empty_description(): void
    {
        $configuration = ConsoleCommandConfiguration::create('channel', 'app:import', []);

        $this->assertSame('', $configuration->getDescription());
    }
}
