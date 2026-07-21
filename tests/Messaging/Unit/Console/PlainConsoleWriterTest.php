<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Console;

use Ecotone\Messaging\Console\PlainConsoleWriter;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class PlainConsoleWriterTest extends TestCase
{
    public function test_writing_semantic_messages_with_level_prefixes(): void
    {
        $stream = fopen('php://memory', 'r+');
        $writer = new PlainConsoleWriter($stream);

        $writer->info('information');
        $writer->success('done');
        $writer->warning('careful');
        $writer->error('failed');

        $this->assertSame(
            '[INFO] information' . PHP_EOL . '[OK] done' . PHP_EOL . '[WARNING] careful' . PHP_EOL . '[ERROR] failed' . PHP_EOL,
            $this->readStream($stream)
        );
    }

    public function test_rendering_table_with_aligned_columns(): void
    {
        $stream = fopen('php://memory', 'r+');
        $writer = new PlainConsoleWriter($stream);

        $writer->table(['Name', 'Status'], [['orders', 'running'], ['payments', 'stopped']]);

        $this->assertSame(
            'Name     | Status' . PHP_EOL . 'orders   | running' . PHP_EOL . 'payments | stopped' . PHP_EOL,
            $this->readStream($stream)
        );
    }

    public function test_rendering_progress_bar_up_to_maximum_steps(): void
    {
        $stream = fopen('php://memory', 'r+');
        $writer = new PlainConsoleWriter($stream);

        $progressBar = $writer->progressBar(3);
        $progressBar->advance();
        $progressBar->advance(2);
        $progressBar->finish();

        $this->assertSame("\r1/3\r3/3" . PHP_EOL, $this->readStream($stream));
    }

    private function readStream($stream): string
    {
        rewind($stream);

        return stream_get_contents($stream);
    }
}
