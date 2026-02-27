<?php

namespace Test\Ecotone\AnnotationFinder\Unit;

use Attribute;
use Ecotone\AnnotationFinder\AnnotationResolver\AttributeResolver;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\PropertyAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\TestingNamespace\Correct\ClassWithPromotedConstructorParameterAttribute;

/**
 * @internal
 */
/**
 * licence Apache-2.0
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

    public function test_child_inherits_class_attribute_from_parent(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForClass(ChildWithNoAttributes::class);

        self::assertCount(1, $attributes);
        self::assertInstanceOf(AttributeA::class, $attributes[0]);
        self::assertSame('parent', $attributes[0]->value);
    }

    public function test_same_attribute_on_child_and_parent_results_in_single_instance_from_child(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForClass(ChildWithSameAttribute::class);

        $attributeAInstances = array_values(array_filter($attributes, fn ($a) => $a instanceof AttributeA));
        self::assertCount(1, $attributeAInstances);
        self::assertSame('child', $attributeAInstances[0]->value);
    }

    public function test_different_attributes_from_parent_and_child_are_merged(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForClass(ChildWithDifferentAttribute::class);

        $classNames = array_map(fn ($a) => $a::class, $attributes);
        self::assertContains(AttributeA::class, $classNames);
        self::assertContains(AttributeB::class, $classNames);
    }

    public function test_multi_level_inheritance_merges_all_unique_attributes(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForClass(GrandchildClass::class);

        $classNames = array_map(fn ($a) => $a::class, $attributes);
        self::assertContains(AttributeA::class, $classNames);
        self::assertContains(AttributeB::class, $classNames);
        self::assertContains(AttributeC::class, $classNames);
    }

    public function test_method_annotations_resolved_from_declaring_parent_class(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForMethod(ChildInheritingMethod::class, 'execute');

        self::assertCount(1, $attributes);
        self::assertInstanceOf(MethodAttributeA::class, $attributes[0]);
    }

    public function test_overridden_method_uses_only_child_annotations(): void
    {
        $resolver = new AttributeResolver();

        $attributes = $resolver->getAnnotationsForMethod(ChildOverridingMethod::class, 'execute');

        $classNames = array_map(fn ($a) => $a::class, $attributes);
        self::assertContains(MethodAttributeB::class, $classNames);
        self::assertNotContains(MethodAttributeA::class, $classNames);
    }
}

#[Attribute]
class AttributeA
{
    public function __construct(public string $value = '')
    {
    }
}

#[Attribute]
class AttributeB
{
}

#[Attribute]
class AttributeC
{
}

#[Attribute]
class MethodAttributeA
{
}

#[Attribute]
class MethodAttributeB
{
}

#[AttributeA(value: 'parent')]
class InheritanceParent
{
}

#[AttributeA(value: 'child')]
class ChildWithSameAttribute extends InheritanceParent
{
}

#[AttributeB]
class ChildWithDifferentAttribute extends InheritanceParent
{
}

class ChildWithNoAttributes extends InheritanceParent
{
}

#[AttributeC]
class GrandchildClass extends ChildWithDifferentAttribute
{
}

class ParentWithAnnotatedMethod
{
    #[MethodAttributeA]
    public function execute(): void
    {
    }
}

class ChildInheritingMethod extends ParentWithAnnotatedMethod
{
}

class ChildOverridingMethod extends ParentWithAnnotatedMethod
{
    #[MethodAttributeB]
    public function execute(): void
    {
    }
}
