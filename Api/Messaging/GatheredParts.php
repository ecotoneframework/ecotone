<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class GatheredParts
{
    public function __construct(
        public readonly array $results,
        public readonly array $missingPartNumbers,
        public readonly int $size,
        public readonly mixed $originalPayload,
        public readonly GatheredReleaseReason $releaseReason,
    ) {
    }
}
