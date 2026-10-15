<?php

namespace TestingNamespace\Correct;

use Ecotone\Api\Attribute\ServiceContext;

/**
 * licence Apache-2.0
 */
class CorrectMessagingClass
{
    #[ServiceContext]
    public function someExtension(): array
    {
        return [];
    }
}
