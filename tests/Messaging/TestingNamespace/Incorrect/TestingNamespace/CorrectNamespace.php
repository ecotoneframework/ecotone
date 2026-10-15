<?php

namespace Incorrect\TestingNamespace;

use Ecotone\Api\Attribute\ServiceContext;

/**
 * licence Apache-2.0
 */
class CorrectNamespace
{
    #[ServiceContext]
    public function someExtension(): array
    {
        return [];
    }
}
