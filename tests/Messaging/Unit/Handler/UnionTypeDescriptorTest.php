<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Countable;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\Type\UnionType;
use Iterator;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class UnionTypeDescriptorTest extends TestCase
{
    public function test_union_type_compatibility_between_scalars()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::string(), Type::int(),
            ])->isCompatibleWith(Type::int())
        );

        $this->assertTrue(
            Type::int()
                ->isCompatibleWith(UnionType::createWith([
                    Type::string(), Type::int(),
                ]))
        );
    }

    public function test_union_type_compatibility_between_scalar_and_object_type()
    {
        $this->assertFalse(
            UnionType::createWith([
                Type::string(), Type::int(),
            ])->isCompatibleWith(Type::object())
        );

        $this->assertFalse(
            Type::object()
                ->isCompatibleWith(UnionType::createWith([
                    Type::string(), Type::int(),
                ]))
        );
    }

    public function test_union_type_compatibility_between_scalar_and_class()
    {
        $this->assertFalse(
            UnionType::createWith([
                Type::create(Countable::class), Type::create(stdClass::class),
            ])->isCompatibleWith(Type::string())
        );

        $this->assertFalse(
            Type::string()
                ->isCompatibleWith(UnionType::createWith([
                    Type::create(Countable::class), Type::create(stdClass::class),
                ]))
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::create(Countable::class), Type::create(stdClass::class),
            ])->isCompatibleWith(
                UnionType::createWith([
                    Type::string(), Type::int(),
                ])
            )
        );
    }

    public function test_if_is_class_of_type()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::create(Countable::class), Type::create(stdClass::class),
            ])->isClassOfType(Countable::class)
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::create(Countable::class), Type::create(stdClass::class),
            ])->isClassOfType(Iterator::class)
        );
    }

    public function test_ignoring_duplicated_types()
    {
        $this->assertEquals(
            Type::create(Type::ARRAY),
            UnionType::createWith([
                Type::create(Type::ARRAY), Type::create(Type::ARRAY),
            ])
        );
    }

    public function test_if_is_iterable()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::array(),
            ])->isIterable()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::create(Countable::class), Type::create(stdClass::class),
            ])->isIterable()
        );
    }

    public function test_if_is_collection()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::createCollection(stdClass::class),
            ])->isCollection()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::create(Countable::class),
            ])->isCollection()
        );
    }

    public function test_if_is_non_collection_array()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::array(),
            ])->isArrayButNotClassBasedCollection()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::createCollection(stdClass::class),
            ])->isArrayButNotClassBasedCollection()
        );
    }

    public function test_if_is_boolean()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::boolean(),
            ])->isBoolean()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::array(),
            ])->isBoolean()
        );
    }

    public function test_if_is_void()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::void(),
            ])->isVoid()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::array(),
            ])->isVoid()
        );
    }

    public function test_if_is_string()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::string(),
            ])->isString()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::array(),
            ])->isString()
        );
    }

    public function test_if_is_compound_object_type()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::object(),
            ])->isCompoundObjectType()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::array(),
            ])->isCompoundObjectType()
        );
    }

    public function test_if_is_interface()
    {
        $this->assertTrue(
            UnionType::createWith([
                Type::object(Countable::class),
            ])->isInterface()
        );

        $this->assertFalse(
            UnionType::createWith([
                Type::object(stdClass::class),
            ])->isInterface()
        );
    }

    public function test_not_equal_when_comparing_with_single_type()
    {
        $this->assertFalse(
            UnionType::createWith([Type::int(), Type::string()])
                ->equals(Type::string())
        );
    }

    public function test_equality_when_unordered_types()
    {
        $this->assertTrue(
            Type::create('string|int')
                ->equals(Type::create('int|string'))
        );
    }

    public function test_not_equality_when_this_contains_more_types_than_compared_one()
    {
        $this->assertFalse(
            Type::create('string|int|float')
                ->equals(Type::create('string|int'))
        );
    }

    public function test_not_equality_when_compared_contains_more_than_this()
    {
        $this->assertFalse(
            Type::create('string|int')
                ->equals(Type::create('string|int|float'))
        );
    }

    public function test_creating_with_one_type_result_in_type_descriptor()
    {
        $this->assertEquals(
            Type::string(),
            UnionType::createWith([Type::string()])
        );
    }
}
