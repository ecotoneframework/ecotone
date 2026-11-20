<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Execution;

use Closure;

/**
 * licence Apache-2.0
 */
final class OneExecutionAtATime
{
    private ?object $turn = null;
    private ?object $executionHavingTheTurn = null;

    public function isTakenByAnotherExecution(): bool
    {
        return $this->executionHavingTheTurn !== null && $this->executionHavingTheTurn !== ExecutionScope::current();
    }

    /**
     * @template T
     * @param Closure(): T $work
     * @return T
     */
    public function run(Closure $work): mixed
    {
        $channelClass = ExecutionScope::coroutineChannelClass();
        $execution = ExecutionScope::current();
        if ($channelClass === null || $this->executionHavingTheTurn === $execution) {
            return $work();
        }

        $this->turn ??= new $channelClass(1);
        $this->turn->push(true);
        $this->executionHavingTheTurn = $execution;
        try {
            return $work();
        } finally {
            $this->executionHavingTheTurn = null;
            $this->turn->pop();
        }
    }
}
