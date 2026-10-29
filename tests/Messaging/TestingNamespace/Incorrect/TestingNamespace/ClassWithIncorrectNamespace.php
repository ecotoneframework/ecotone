<?php

declare(strict_types=1);

namespace Incorrect\TestingNamespace\Wrong;

use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * licence Apache-2.0
 */
class ClassWithIncorrectNamespaceAndClassName
{
    #[ServiceContext]
    public function someExtension(): array
    {
        return [];
    }
}
