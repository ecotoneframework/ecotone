<?php

namespace TestingNamespace\Correct;

use Ecotone\Messaging\Attribute\ServiceContext;

class CorrectMessagingClass
{
    #[ServiceContext]
    public function someExtension(): array
    {
        return [];
    }
}
