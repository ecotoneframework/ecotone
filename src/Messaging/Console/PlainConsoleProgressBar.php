<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
final class PlainConsoleProgressBar implements ConsoleProgressBar
{
    private int $currentStep = 0;

    public function __construct(private ConsoleWriter $writer, private int $maxSteps)
    {
    }

    public function advance(int $steps = 1): void
    {
        $this->currentStep += $steps;
        $this->writer->write("\r" . $this->renderProgress());
    }

    public function finish(): void
    {
        if ($this->maxSteps > 0 && $this->currentStep < $this->maxSteps) {
            $this->currentStep = $this->maxSteps;
            $this->writer->write("\r" . $this->renderProgress());
        }
        $this->writer->writeln();
    }

    private function renderProgress(): string
    {
        return $this->maxSteps > 0
            ? "{$this->currentStep}/{$this->maxSteps}"
            : (string) $this->currentStep;
    }
}
