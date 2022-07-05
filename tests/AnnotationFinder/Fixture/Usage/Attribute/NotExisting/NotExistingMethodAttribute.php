<?php


namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;


use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

class NotExistingMethodAttribute
{
    #[SomeGatewayExample]
    #[Johny('bla')]
    public function test()
    {

    }
}