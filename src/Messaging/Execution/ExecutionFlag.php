<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Execution;

/**
 * licence Apache-2.0
 */
final class ExecutionFlag
{
    public function __construct(public bool $isRaised)
    {
    }
}
