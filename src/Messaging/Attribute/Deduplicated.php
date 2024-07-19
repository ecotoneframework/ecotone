<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class Deduplicated
{
    public function __construct(private string $deduplicationHeaderName = '')
    {
    }

    public function getDeduplicationHeaderName(): string
    {
        return $this->deduplicationHeaderName;
    }
}
