<?php


namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;


use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

class NotExistingPropertyAttribute
{
    #[Johny('bla')]
    private string $some;
}