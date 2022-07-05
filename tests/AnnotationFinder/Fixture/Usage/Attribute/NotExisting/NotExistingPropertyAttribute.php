<?php


namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;


use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

class NotExistingPropertyAttribute
{
    #[Johny('bla')]
    private string $some;
}