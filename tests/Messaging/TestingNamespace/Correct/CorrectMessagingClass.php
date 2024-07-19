<?php

namespace TestingNamespace\Correct;

use Ecotone\Messaging\Attribute\ServiceContext;

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
