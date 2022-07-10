<?php


namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

#[Johny('bla')]
class NotExistingClassAttribute
{
    #[SomeHandlerAnnotation]
    public function test()
    {

    }
}