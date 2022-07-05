<?php


namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

#[Johny('bla')]
class NotExistingClassAttribute
{
    #[SomeHandlerAnnotation]
    public function test()
    {

    }
}