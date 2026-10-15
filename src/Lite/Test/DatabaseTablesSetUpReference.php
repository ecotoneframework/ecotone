<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

/**
 * licence Apache-2.0
 */
final class DatabaseTablesSetUpReference
{
    public function __construct(
        public readonly string $referenceName,
    ) {
    }
}
