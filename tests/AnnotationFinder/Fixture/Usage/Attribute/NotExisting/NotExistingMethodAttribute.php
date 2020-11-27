<?php


namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;


use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

class NotExistingMethodAttribute
{
    #[SomeGatewayExample]
    #[Johny('bla')]
    public function test()
    {

    }
}