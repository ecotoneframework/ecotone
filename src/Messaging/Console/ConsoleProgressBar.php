<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Console;

/**
 * licence Apache-2.0
 */
interface ConsoleProgressBar
{
    public function advance(int $steps = 1): void;

    public function finish(): void;
}
