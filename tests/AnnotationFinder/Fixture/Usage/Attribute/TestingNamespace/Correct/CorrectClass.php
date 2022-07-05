<?php


namespace TestingNamespace\Correct;

use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
class CorrectClass
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}