<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class EventTag
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $value = null,
    ) {
    }
}
