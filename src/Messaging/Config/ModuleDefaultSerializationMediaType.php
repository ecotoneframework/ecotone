<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

/**
 * licence Apache-2.0
 */
final class ModuleDefaultSerializationMediaType
{
    public function __construct(public readonly string $mediaType)
    {
    }
}
