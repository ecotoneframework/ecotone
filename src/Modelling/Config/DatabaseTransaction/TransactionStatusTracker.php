<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\DatabaseTransaction;

/**
 * licence Apache-2.0
 */
final class TransactionStatusTracker
{
    public function __construct(
        private bool $isInsideTransaction
    ) {
    }

    public function markAsInsideTransaction(): void
    {
        $this->isInsideTransaction = true;
    }

    public function markAsOutsideTransaction(): void
    {
        $this->isInsideTransaction = false;
    }

    public function isInsideTransaction(): bool
    {
        return $this->isInsideTransaction;
    }
}
