<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UnionTypeDescriptorTest extends TestCase
{
    public function test_union_type_compatibility_between_scalars()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()
            ])->isCompatibleWith(TypeDescriptor::createIntegerType())
        );

        $this->assertTrue(
            TypeDescriptor::createIntegerType()
                ->isCompatibleWith(UnionTypeDescriptor::createWith([
                    TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()
                ]))
        );
    }

    public function test_union_type_compatibility_between_scalar_and_object_type()
    {
        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()
            ])->isCompatibleWith(TypeDescriptor::create(TypeDescriptor::OBJECT))
        );

        $this->assertFalse(
            TypeDescriptor::create(TypeDescriptor::OBJECT)
                ->isCompatibleWith(UnionTypeDescriptor::createWith([
                    TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()
                ]))
        );
    }

    public function test_union_type_compatibility_between_scalar_and_class()
    {
        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
            ])->isCompatibleWith(TypeDescriptor::createStringType())
        );

        $this->assertFalse(
            TypeDescriptor::createStringType()
                ->isCompatibleWith(UnionTypeDescriptor::createWith([
                    TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
                ]))
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
            ])->isCompatibleWith(
                UnionTypeDescriptor::createWith([
                    TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()
                ])
            )
        );

    }

    public function test_if_is_class_of_type()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
            ])->isClassOfType(\Countable::class)
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
            ])->isClassOfType(\Iterator::class)
        );
    }

    public function test_ignoring_duplicated_types()
    {
        $this->assertEquals(
            TypeDescriptor::create(TypeDescriptor::ARRAY),
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(TypeDescriptor::ARRAY), TypeDescriptor::create(TypeDescriptor::ARRAY)
            ])
        );
    }

    public function test_if_is_iterable()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isIterable()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class), TypeDescriptor::create(\stdClass::class)
            ])->isIterable()
        );
    }

    public function test_if_is_collection()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createCollection(\stdClass::class)
            ])->isCollection()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class)
            ])->isCollection()
        );
    }

    public function test_if_is_non_collection_array()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isNonCollectionArray()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createCollection(\stdClass::class)
            ])->isNonCollectionArray()
        );
    }

    public function test_if_is_boolean()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createBooleanType()
            ])->isBoolean()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isBoolean()
        );
    }

    public function test_if_is_void()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(TypeDescriptor::VOID)
            ])->isVoid()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isVoid()
        );
    }

    public function test_if_is_string()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createStringType()
            ])->isString()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isString()
        );
    }

    public function test_if_is_compound_object_type()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(TypeDescriptor::OBJECT)
            ])->isCompoundObjectType()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createArrayType()
            ])->isCompoundObjectType()
        );
    }

    public function test_if_is_primitive()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::createStringType()
            ])->isPrimitive()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\stdClass::class)
            ])->isPrimitive()
        );
    }

    public function test_if_is_interface()
    {
        $this->assertTrue(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\Countable::class)
            ])->isInterface()
        );

        $this->assertFalse(
            UnionTypeDescriptor::createWith([
                TypeDescriptor::create(\stdClass::class)
            ])->isInterface()
        );
    }

    public function test_not_equal_when_comparing_with_single_type()
    {
        $this->assertFalse(
            UnionTypeDescriptor::createWith([TypeDescriptor::createIntegerType(), TypeDescriptor::createStringType()])
                ->equals(TypeDescriptor::createStringType())
        );
    }

    public function test_equality_when_unordered_types()
    {
        $this->assertTrue(
            TypeDescriptor::create("string|int")
                ->equals(TypeDescriptor::create("int|string"))
        );
    }

    public function test_not_equality_when_this_contains_more_types_than_compared_one()
    {
        $this->assertFalse(
            TypeDescriptor::create("string|int|float")
                ->equals(TypeDescriptor::create("string|int"))
        );
    }

    public function test_not_equality_when_compared_contains_more_than_this()
    {
        $this->assertFalse(
            TypeDescriptor::create("string|int")
                ->equals(TypeDescriptor::create("string|int|float"))
        );
    }

    public function test_creating_with_one_type_result_in_type_descriptor()
    {
        $this->assertEquals(
            TypeDescriptor::createStringType(),
            UnionTypeDescriptor::createWith([TypeDescriptor::createStringType()])
        );
    }
}