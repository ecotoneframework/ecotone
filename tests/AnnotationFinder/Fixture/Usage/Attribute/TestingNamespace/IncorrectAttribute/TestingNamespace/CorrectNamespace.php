<?php


namespace IncorrectAttribute\TestingNamespace;


use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[ApplicationContext]
class CorrectNamespace
{
    #[Extension]
    public function someExtension() : array
    {
        return [];
    }
}