<?php


namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

#[Johny('bla')]
class NotExistingClassAttribute
{
    #[SomeHandlerAnnotation]
    public function test()
    {

    }
}