<?php

namespace Incorrect\TestingNamespace;

use Ecotone\Messaging\Attribute\ServiceContext;

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
