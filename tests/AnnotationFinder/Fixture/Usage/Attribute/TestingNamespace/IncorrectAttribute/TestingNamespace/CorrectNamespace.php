<?php


namespace IncorrectAttribute\TestingNamespace;


use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
class CorrectNamespace
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}