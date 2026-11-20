<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class CollectedMessages
{
    /** @param list<CollectedMessage> $messages */
    public function __construct(
        public readonly array $messages,
        public readonly ?string $groupKey,
        public readonly CollectedReleaseReason $releaseReason,
    ) {
    }
}
