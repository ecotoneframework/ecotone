<?php

namespace TestingNamespace\Correct;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
/**
 * licence Apache-2.0
 */
class CorrectClass
{
    #[Extension]
    public function someExtension(): array
    {
        return [];
    }
}
