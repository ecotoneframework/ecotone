<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ConsoleCommandWithArrayOptions;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ConsoleCommandWithMessageHeaders;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithIncorrectResultSet;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\StdClassConverter;

/**
 * Class ConsoleCommandModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ConsoleCommandModuleTest extends AnnotationConfigurationTestCase
{
    public function test_throwing_exception_when_one_time_command_having_incorrect_return_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                OneTimeWithIncorrectResultSet::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_execute_console_command_with_array_of_options()
    {
        $consoleCommand = new ConsoleCommandWithArrayOptions();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithArrayOptions::class],
            [$consoleCommand]
        );

        $ecotoneLite->runConsoleCommand('cli-command:array-options', [
            'names' => ['one', 'two'],
        ]);

        $this->assertEquals(
            [['one', 'two']],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_command_with_array_of_options_and_argument()
    {
        $consoleCommand = new ConsoleCommandWithArrayOptions();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithArrayOptions::class],
            [$consoleCommand]
        );

        $ecotoneLite->runConsoleCommand('cli-command:array-options-and-argument', [
            'email' => 'test@example.com',
            'names' => ['one', 'two'],
        ]);

        $this->assertEquals(
            ['test@example.com', ['one', 'two']],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_extra_header_values()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $ecotoneLite->runConsoleCommand('cli-command:with-headers', [
            'content' => 'Hello World',
            'header' => ['email:test@example.com'],
        ]);

        $this->assertEquals(
            ['Hello World', 'test@example.com'],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_multiple_extra_header_values()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $ecotoneLite->runConsoleCommand('cli-command:with-multiple-headers', [
            'content' => 'Hello World',
            'header' => ['supportive_email:test@example.com', 'billing_email:test2@example.com'],
        ]);

        $this->assertEquals(
            ['Hello World', 'test@example.com', 'test2@example.com'],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_incorrect_header_value()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotoneLite->runConsoleCommand('cli-command:with-headers', [
            'content' => 'Hello World',
            'header' => ['email'],
        ]);
    }
}
