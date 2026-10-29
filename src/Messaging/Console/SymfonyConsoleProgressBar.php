<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * licence Apache-2.0
 */
final class SymfonyConsoleProgressBar implements ConsoleProgressBar
{
    public function __construct(private ProgressBar $progressBar)
    {
    }

    public function advance(int $steps = 1): void
    {
        $this->progressBar->advance($steps);
    }

    public function finish(): void
    {
        $this->progressBar->finish();
    }
}
