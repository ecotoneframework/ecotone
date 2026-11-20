<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class CollectedMessage
{
    /** @param array<string, mixed> $headers */
    public function __construct(public readonly mixed $payload, public readonly array $headers)
    {
    }
}
