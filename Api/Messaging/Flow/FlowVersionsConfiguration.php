<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowVersionsConfiguration
{
    private function __construct(public readonly string $location)
    {
    }

    public static function createWithLocation(string $location): self
    {
        return new self($location);
    }
}
