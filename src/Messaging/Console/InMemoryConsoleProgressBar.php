<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
final class InMemoryConsoleProgressBar implements ConsoleProgressBar
{
    private int $currentStep = 0;
    private bool $isFinished = false;

    public function __construct(private int $maxSteps)
    {
    }

    public function advance(int $steps = 1): void
    {
        $this->currentStep += $steps;
    }

    public function finish(): void
    {
        $this->isFinished = true;
    }

    public function getMaxSteps(): int
    {
        return $this->maxSteps;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function isFinished(): bool
    {
        return $this->isFinished;
    }
}
