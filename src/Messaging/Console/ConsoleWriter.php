<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
interface ConsoleWriter
{
    public function write(string $message): void;

    public function writeln(string $message = ''): void;

    public function info(string $message): void;

    public function success(string $message): void;

    public function warning(string $message): void;

    public function error(string $message): void;

    /**
     * @param array<int, string> $columnHeaders
     * @param array<int, array<int, mixed>> $rows
     */
    public function table(array $columnHeaders, array $rows): void;

    public function progressBar(int $maxSteps = 0): ConsoleProgressBar;
}
