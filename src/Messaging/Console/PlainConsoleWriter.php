<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
final class PlainConsoleWriter implements ConsoleWriter
{
    /**
     * @param resource|null $stream
     */
    public function __construct(private $stream = null)
    {
    }

    public function write(string $message): void
    {
        fwrite($this->getStream(), $message);
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function info(string $message): void
    {
        $this->writeln('[INFO] ' . $message);
    }

    public function success(string $message): void
    {
        $this->writeln('[OK] ' . $message);
    }

    public function warning(string $message): void
    {
        $this->writeln('[WARNING] ' . $message);
    }

    public function error(string $message): void
    {
        $this->writeln('[ERROR] ' . $message);
    }

    public function table(array $columnHeaders, array $rows): void
    {
        $columnWidths = $this->resolveColumnWidths($columnHeaders, $rows);

        $this->writeln($this->formatRow($columnHeaders, $columnWidths));
        foreach ($rows as $row) {
            $this->writeln($this->formatRow($row, $columnWidths));
        }
    }

    public function progressBar(int $maxSteps = 0): ConsoleProgressBar
    {
        return new PlainConsoleProgressBar($this, $maxSteps);
    }

    private function resolveColumnWidths(array $columnHeaders, array $rows): array
    {
        $columnWidths = [];
        foreach (array_merge([$columnHeaders], $rows) as $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $columnWidths[$columnIndex] = max($columnWidths[$columnIndex] ?? 0, strlen((string) $value));
            }
        }

        return $columnWidths;
    }

    private function formatRow(array $row, array $columnWidths): string
    {
        $formattedColumns = [];
        foreach (array_values($row) as $columnIndex => $value) {
            $formattedColumns[] = str_pad((string) $value, $columnWidths[$columnIndex]);
        }

        return rtrim(implode(' | ', $formattedColumns));
    }

    private function getStream()
    {
        return $this->stream ?? STDOUT;
    }
}
