<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Console;

use Ecotone\Messaging\Console\SymfonyConsoleWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * licence Apache-2.0
 * @internal
 */
final class SymfonyConsoleWriterTest extends TestCase
{
    public function test_writing_colored_semantic_messages(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $writer = new SymfonyConsoleWriter($output);

        $writer->info('information');
        $writer->success('done');
        $writer->warning('careful');
        $writer->error('failed');

        $content = $output->fetch();
        $this->assertStringContainsString("\033[36minformation\033[39m", $content);
        $this->assertStringContainsString("\033[32mdone\033[39m", $content);
        $this->assertStringContainsString("\033[33mcareful\033[39m", $content);
        $this->assertStringContainsString("\033[31mfailed\033[39m", $content);
    }

    public function test_rendering_table(): void
    {
        $output = new BufferedOutput();
        $writer = new SymfonyConsoleWriter($output);

        $writer->table(['Name', 'Status'], [['orders', 'running']]);

        $content = $output->fetch();
        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('orders', $content);
        $this->assertStringContainsString('running', $content);
    }

    public function test_rendering_progress_bar(): void
    {
        $output = new BufferedOutput();
        $writer = new SymfonyConsoleWriter($output);

        $progressBar = $writer->progressBar(3);
        $progressBar->advance();
        $progressBar->advance(2);
        $progressBar->finish();

        $this->assertStringContainsString('3/3', $output->fetch());
    }
}
