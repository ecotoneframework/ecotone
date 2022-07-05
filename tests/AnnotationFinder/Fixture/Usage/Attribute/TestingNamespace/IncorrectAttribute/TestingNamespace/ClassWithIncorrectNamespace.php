<?php
declare(strict_types=1);

namespace IncorrectAttribute\TestingNamespace\Wrong;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

class ClassWithIncorrectNamespaceAndClassName
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}