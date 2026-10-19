<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
interface CurrentTrace
{
    public function tracingId(): ?string;
}
