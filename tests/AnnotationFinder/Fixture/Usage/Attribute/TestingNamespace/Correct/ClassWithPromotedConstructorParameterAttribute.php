<?php

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\TestingNamespace\Correct;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ParameterAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\PropertyAttribute;

class ClassWithPromotedConstructorParameterAttribute
{
    public function __construct(
        #[ParameterAttribute, PropertyAttribute] public string $aPromotedProperty,
    ) {
    }
}
