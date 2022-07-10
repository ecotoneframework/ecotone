<?php
declare(strict_types=1);

namespace IncorrectAttribute\TestingNamespace\Wrong;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
class ClassWithIncorrectNamespaceAndClassName
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}