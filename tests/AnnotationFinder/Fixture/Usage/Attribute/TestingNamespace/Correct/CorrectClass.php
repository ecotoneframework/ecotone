<?php


namespace TestingNamespace\Correct;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
class CorrectClass
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}