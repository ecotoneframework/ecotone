<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * licence Apache-2.0
 */
final class SymfonyConsoleWriter implements ConsoleWriter
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function write(string $message): void
    {
        $this->output->write($message);
    }

    public function writeln(string $message = ''): void
    {
        $this->output->writeln($message);
    }

    public function info(string $message): void
    {
        $this->output->writeln('<fg=cyan>' . $message . '</>');
    }

    public function success(string $message): void
    {
        $this->output->writeln('<fg=green>' . $message . '</>');
    }

    public function warning(string $message): void
    {
        $this->output->writeln('<fg=yellow>' . $message . '</>');
    }

    public function error(string $message): void
    {
        $this->output->writeln('<fg=red>' . $message . '</>');
    }

    public function table(array $columnHeaders, array $rows): void
    {
        $table = new Table($this->output);
        $table->setHeaders($columnHeaders)
            ->setRows($rows);

        $table->render();
    }

    public function progressBar(int $maxSteps = 0): ConsoleProgressBar
    {
        return new SymfonyConsoleProgressBar(new ProgressBar($this->output, $maxSteps));
    }
}
