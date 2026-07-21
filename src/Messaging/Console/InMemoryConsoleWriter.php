<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
final class InMemoryConsoleWriter implements ConsoleWriter
{
    private string $writtenContent = '';
    /** @var string[] */
    private array $infoLines = [];
    /** @var string[] */
    private array $successLines = [];
    /** @var string[] */
    private array $warningLines = [];
    /** @var string[] */
    private array $errorLines = [];
    /** @var array<int, array{columnHeaders: array<int, string>, rows: array<int, array<int, mixed>>}> */
    private array $tables = [];
    /** @var InMemoryConsoleProgressBar[] */
    private array $progressBars = [];

    public function write(string $message): void
    {
        $this->writtenContent .= $message;
    }

    public function writeln(string $message = ''): void
    {
        $this->writtenContent .= $message . PHP_EOL;
    }

    public function info(string $message): void
    {
        $this->infoLines[] = $message;
    }

    public function success(string $message): void
    {
        $this->successLines[] = $message;
    }

    public function warning(string $message): void
    {
        $this->warningLines[] = $message;
    }

    public function error(string $message): void
    {
        $this->errorLines[] = $message;
    }

    public function table(array $columnHeaders, array $rows): void
    {
        $this->tables[] = ['columnHeaders' => $columnHeaders, 'rows' => $rows];
    }

    public function progressBar(int $maxSteps = 0): ConsoleProgressBar
    {
        $progressBar = new InMemoryConsoleProgressBar($maxSteps);
        $this->progressBars[] = $progressBar;

        return $progressBar;
    }

    public function getWrittenContent(): string
    {
        return $this->writtenContent;
    }

    /**
     * @return string[]
     */
    public function getInfoLines(): array
    {
        return $this->infoLines;
    }

    /**
     * @return string[]
     */
    public function getSuccessLines(): array
    {
        return $this->successLines;
    }

    /**
     * @return string[]
     */
    public function getWarningLines(): array
    {
        return $this->warningLines;
    }

    /**
     * @return string[]
     */
    public function getErrorLines(): array
    {
        return $this->errorLines;
    }

    /**
     * @return array<int, array{columnHeaders: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return InMemoryConsoleProgressBar[]
     */
    public function getProgressBars(): array
    {
        return $this->progressBars;
    }
}
