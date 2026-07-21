<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
final class DelegatingConsoleWriter implements ConsoleWriter
{
    public function __construct(private ConsoleWriter $delegate)
    {
    }

    public function executeWith(ConsoleWriter $writer, callable $execution): mixed
    {
        $previousDelegate = $this->delegate;
        $this->delegate = $writer;
        try {
            return $execution();
        } finally {
            $this->delegate = $previousDelegate;
        }
    }

    public function write(string $message): void
    {
        $this->delegate->write($message);
    }

    public function writeln(string $message = ''): void
    {
        $this->delegate->writeln($message);
    }

    public function info(string $message): void
    {
        $this->delegate->info($message);
    }

    public function success(string $message): void
    {
        $this->delegate->success($message);
    }

    public function warning(string $message): void
    {
        $this->delegate->warning($message);
    }

    public function error(string $message): void
    {
        $this->delegate->error($message);
    }

    public function table(array $columnHeaders, array $rows): void
    {
        $this->delegate->table($columnHeaders, $rows);
    }

    public function progressBar(int $maxSteps = 0): ConsoleProgressBar
    {
        return $this->delegate->progressBar($maxSteps);
    }
}
