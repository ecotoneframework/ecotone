<?php
declare(strict_types=1);

namespace Incorrect\TestingNamespace\Wrong;

use Ecotone\Messaging\Annotation\ApplicationContext;

class ClassWithIncorrectNamespaceAndClassName
{
    /**
     * @return array
     * @ApplicationContext()
     */
    public function someExtension(): array
    {
        return [];
    }
}