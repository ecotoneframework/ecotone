<?php

namespace Test\Ecotone\AnnotationFinder\Unit;

use Ecotone\AnnotationFinder\AnnotationResolver\AttributeResolver;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\PropertyAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\TestingNamespace\Correct\ClassWithPromotedConstructorParameterAttribute;

/**
 * @internal
 */
class AttributeResolverTest extends TestCase
{
    public function test_it_can_resolve_property_attributes_on_promoted_constructor_parameters(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForProperty(ClassWithPromotedConstructorParameterAttribute::class, 'aPromotedProperty');

        self::assertEquals([new PropertyAttribute()], $attributes);
    }
}
