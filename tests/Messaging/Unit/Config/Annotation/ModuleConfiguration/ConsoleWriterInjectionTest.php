<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\Console\ConsoleWriter;
use Ecotone\Api\Console\InMemoryConsoleWriter;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Console\DelegatingConsoleWriter;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsoleWriterInjectionTest extends TestCase
{
    public function test_console_writer_is_injected_into_console_command_handler(): void
    {
        $handler = new class () {
            #[ConsoleCommand('app:greet')]
            public function execute(string $name, ConsoleWriter $writer): void
            {
                $writer->success('Hello ' . $name);
            }
        };
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );

        $inMemoryWriter = new InMemoryConsoleWriter();
        /** @var DelegatingConsoleWriter $delegatingWriter */
        $delegatingWriter = $ecotoneLite->getServiceFromContainer(ConsoleWriter::class);
        $delegatingWriter->executeWith(
            $inMemoryWriter,
            fn () => $ecotoneLite->runConsoleCommand('app:greet', ['name' => 'John'])
        );

        $this->assertSame(['Hello John'], $inMemoryWriter->getSuccessLines());
    }

    public function test_console_output_is_collected_in_memory_during_flow_testing(): void
    {
        $handler = new class () {
            #[ConsoleCommand('app:greet')]
            public function execute(string $name, ConsoleWriter $writer): void
            {
                $writer->success('Hello ' . $name);
            }
        };
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotoneLite->runConsoleCommand('app:greet', ['name' => 'John']);

        $this->assertSame(['Hello John'], $ecotoneLite->getInMemoryConsoleWriter()->getSuccessLines());
    }

    public function test_console_command_handler_can_render_table_and_progress_bar(): void
    {
        $handler = new class () {
            #[ConsoleCommand('app:report')]
            public function execute(ConsoleWriter $writer): void
            {
                $writer->table(['Name'], [['Order']]);
                $progressBar = $writer->progressBar(2);
                $progressBar->advance();
                $progressBar->advance();
                $progressBar->finish();
            }
        };
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );

        $inMemoryWriter = new InMemoryConsoleWriter();
        /** @var DelegatingConsoleWriter $delegatingWriter */
        $delegatingWriter = $ecotoneLite->getServiceFromContainer(ConsoleWriter::class);
        $delegatingWriter->executeWith(
            $inMemoryWriter,
            fn () => $ecotoneLite->runConsoleCommand('app:report', [])
        );

        $this->assertSame([['columnHeaders' => ['Name'], 'rows' => [['Order']]]], $inMemoryWriter->getTables());
        $progressBar = $inMemoryWriter->getProgressBars()[0];
        $this->assertSame(2, $progressBar->getCurrentStep());
        $this->assertSame(2, $progressBar->maxSteps);
        $this->assertTrue($progressBar->isFinished());
    }

    public function test_previous_writer_is_restored_when_console_command_fails(): void
    {
        $handler = new class () {
            #[ConsoleCommand('app:fail')]
            public function execute(ConsoleWriter $writer): void
            {
                throw new RuntimeException('failure');
            }
        };
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );

        $commandWriter = new InMemoryConsoleWriter();
        $restoredWriter = new InMemoryConsoleWriter();
        /** @var DelegatingConsoleWriter $delegatingWriter */
        $delegatingWriter = $ecotoneLite->getServiceFromContainer(ConsoleWriter::class);

        $delegatingWriter->executeWith(
            $restoredWriter,
            function () use ($delegatingWriter, $commandWriter, $ecotoneLite) {
                try {
                    $delegatingWriter->executeWith(
                        $commandWriter,
                        fn () => $ecotoneLite->runConsoleCommand('app:fail', [])
                    );
                } catch (Exception) {
                }

                $delegatingWriter->success('after failure');
            }
        );

        $this->assertSame([], $commandWriter->getSuccessLines());
        $this->assertSame(['after failure'], $restoredWriter->getSuccessLines());
    }
}
