<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\DatabaseTransaction;

use Ecotone\Messaging\Execution\ExecutionFlag;
use Ecotone\Messaging\Execution\ExecutionScope;

/**
 * licence Apache-2.0
 */
final class TransactionStatusTracker
{
    public function __construct(
        private readonly bool $isInsideTransaction
    ) {
    }

    public function markAsInsideTransaction(): void
    {
        $this->flag()->isRaised = true;
    }

    public function markAsOutsideTransaction(): void
    {
        $this->flag()->isRaised = false;
    }

    public function isInsideTransaction(): bool
    {
        return $this->flag()->isRaised;
    }

    private function flag(): ExecutionFlag
    {
        return ExecutionScope::stateOf($this, fn () => new ExecutionFlag($this->isInsideTransaction));
    }
}
