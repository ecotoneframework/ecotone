<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Messaging\Fixture\Dto\OrderExample;
use Test\Ecotone\Messaging\Fixture\Handler\DumbMessageHandlerBuilder;

/**
 * Class TypeDescriptorTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeDescriptorTest extends TestCase
{
    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_compound_type_and_array_of_scalar_type()
    {
        $typeDescription = TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  "array<string>");

        $this->assertEquals(
            'array<string>',
            $typeDescription->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_null()
    {
        $this->assertEquals(
            TypeDescriptor::NULL,
            ($typeDescription = TypeDescriptor::create("null"))->getTypeHint()
        );
    }

    public function test_guessing_unknown_type_if_only_empty_strings_passed()
    {
        $this->assertEquals(
            TypeDescriptor::ANYTHING,
            ($typeDescription = TypeDescriptor::createWithDocBlock("    ", "    "))->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_returning_base_type_when_docblock_is_incorrect()
    {
        $this->assertEquals(
            TypeDescriptor::createArrayType(),
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  "array<bla>")
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_type_hint_is_incorrect()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock("bla",  TypeDescriptor::ARRAY);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_incompatible_resource_type_hint_and_scalar_union_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::create(TypeDescriptor::RESOURCE."|".TypeDescriptor::INTEGER);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_incompatible_resource_hint_and_compound_union_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::create(TypeDescriptor::RESOURCE."|".  TypeDescriptor::ARRAY);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_incompatible_compound_hint_and_resource_union_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::create(TypeDescriptor::ITERABLE."|".  TypeDescriptor::RESOURCE);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_converting_doc_block_array_type_to_generic()
    {
        $this->assertEquals(
            "array<stdClass>",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\stdClass[]")->getTypeHint()
        );
    }

    public function test_when_parameter_is_array_accepting_only_array_like_type_from_docblock()
    {
        $this->assertEquals(
            "array<stdClass>",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY, "\stdClass|array<\stdClass>|int")->toString()
        );
    }

    public function test_when_parameter_is_union_with_array_accepting_only_array_like_type_from_docblock()
    {
        $this->assertEquals(
            "array<stdClass>|stdClass",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY . "|" . \stdClass::class, "string|array<\stdClass>|int")->toString()
        );
    }

    public function test_ignoring_docblock_if_iterable_or_array_when_declaration_is_array()
    {
        $this->assertEquals(
            TypeDescriptor::ARRAY,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY, "array|iterable")->toString()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_choosing_doc_block_type_hint_over_array()
    {
        $this->assertEquals(
            "array<stdClass>",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY, "array<\stdClass>")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_choosing_doc_block_collection_type_hint_over_compound()
    {
        $typeDescriptor = TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\ArrayCollection<\stdClass>");

        $this->assertEquals(
            "ArrayCollection<stdClass>",
            $typeDescriptor->getTypeHint()
        );

        $this->assertEquals(
            [TypeDescriptor::create(\stdClass::class)],
            $typeDescriptor->resolveGenericTypes()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_resolving_collection_type_for_non_collection()
    {
        $typeDescriptor = TypeDescriptor::create(TypeDescriptor::STRING);

        $this->expectException(InvalidArgumentException::class);

        $typeDescriptor->resolveGenericTypes();
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_checking_equality()
    {
        $this->assertTrue(
            TypeDescriptor::create(TypeDescriptor::STRING)
                ->equals(TypeDescriptor::create(TypeDescriptor::STRING))
        );

        $this->assertTrue(
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\stdClass[]")
                ->equals(TypeDescriptor::create("array<\stdClass>"))
        );

        $this->assertFalse(
            TypeDescriptor::create(TypeDescriptor::OBJECT)
                ->equals(TypeDescriptor::create(TypeDescriptor::INTEGER))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_choosing_declaration_type_over_docblock_when_interface()
    {
        $this->assertEquals(
            \Countable::class,
            TypeDescriptor::createWithDocBlock(\Countable::class, \stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_choosing_declaration_type_over_docblock_when_object_type()
    {
        $this->assertEquals(
            TypeDescriptor::OBJECT,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::OBJECT, \stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_ignoring_docblock_if_not_property_is_not_iterable()
    {
        $this->assertEquals(
            TypeDescriptor::ANYTHING,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ANYTHING,  TypeDescriptor::ARRAY)->getTypeHint()
        );
    }

    public function test_choosing_declaration_array_type_over_unknown_docblock_type()
    {
        $this->assertEquals(
            TypeDescriptor::ARRAY,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  TypeDescriptor::ANYTHING)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_prefixed_type()
    {
        $this->assertEquals(
            \stdClass::class,
            TypeDescriptor::create("\stdClass")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_compound_object_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::OBJECT,
            TypeDescriptor::create(TypeDescriptor::OBJECT)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_for_void_return_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::VOID,
            TypeDescriptor::create(TypeDescriptor::VOID)->getTypeHint()
        );
    }

    public function test_is_interface()
    {
        $this->assertTrue(TypeDescriptor::create(MessageHandler::class)->isInterface());
        $this->assertFalse(TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isInterface());
    }

    public function test_is_abstract_class()
    {
        $this->assertTrue(TypeDescriptor::create(MessagingException::class)->isAbstractClass());
        $this->assertFalse(TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isAbstractClass());
        $this->assertFalse(TypeDescriptor::create(TypeDescriptor::OBJECT)->isAbstractClass());
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_mixed_type_result_in_unknown_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::ANYTHING,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ANYTHING,  "mixed")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_anything_with_anything()
    {
        $this->assertTrue(
            TypeDescriptor::createAnythingType()->isCompatibleWith(TypeDescriptor::createAnythingType())
        );
    }

    public function test_no_compatibility_when_comparing_scalar_to_object_type()
    {
        $this->assertFalse(TypeDescriptor::createStringType()->isCompatibleWith(TypeDescriptor::create(TypeDescriptor::OBJECT)));
        $this->assertFalse(TypeDescriptor::create(TypeDescriptor::OBJECT)->isCompatibleWith(TypeDescriptor::createStringType()));
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_class_of_the_same_type()
    {
        $this->assertTrue(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_class_with_scalar()
    {
        $this->assertFalse(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::createIntegerType())
        );

        $this->assertFalse(
            TypeDescriptor::createIntegerType()->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_class_with_compound()
    {
        $this->assertFalse(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::createArrayType())
        );

        $this->assertFalse(
            TypeDescriptor::createArrayType()->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_different_collections()
    {
        $this->assertFalse(
            TypeDescriptor::createCollection(\stdClass::class)->isCompatibleWith(TypeDescriptor::createCollection(Message::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_same_collections()
    {
        $this->assertTrue(
            TypeDescriptor::createCollection(\stdClass::class)->isCompatibleWith(TypeDescriptor::createCollection(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_collection_different_types()
    {
        $this->assertFalse(
            TypeDescriptor::createCollection("string")->isCompatibleWith(TypeDescriptor::createCollection(Uuid::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_scalar_with_compound()
    {
        $this->assertFalse(
            TypeDescriptor::createArrayType()->isCompatibleWith(TypeDescriptor::createIntegerType())
        );

        $this->assertFalse(
            TypeDescriptor::createIntegerType()->isCompatibleWith(TypeDescriptor::createArrayType())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_scalar_with_compound()
    {
        $this->assertFalse(
            TypeDescriptor::createArrayType()->isCompatibleWith(TypeDescriptor::createIntegerType())
        );

        $this->assertFalse(
            TypeDescriptor::createIntegerType()->isCompatibleWith(TypeDescriptor::createArrayType())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_scalar_with_object()
    {
        $this->assertFalse(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::createIntegerType())
        );

        $this->assertFalse(
            TypeDescriptor::createIntegerType()->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_scalar_with_object_containing_to_string_method()
    {
        $this->assertTrue(TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::createStringType()));

        $this->assertFalse(TypeDescriptor::createStringType()->isCompatibleWith(TypeDescriptor::create(DumbMessageHandlerBuilder::class)));
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_different_classes()
    {
        $this->assertFalse(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::create(DumbMessageHandlerBuilder::class))
        );

        $this->assertFalse(
            TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_class_and_its_interface()
    {
        $this->assertTrue(
            TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::create(MessageHandlerBuilder::class))
        );

        $this->assertTrue(
            TypeDescriptor::create(MessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::create(DumbMessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_subclass_interface_with_base_interface()
    {
        $this->assertTrue(
            TypeDescriptor::create(MessageHandlerBuilderWithParameterConverters::class)->isCompatibleWith(TypeDescriptor::create(MessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_interface_with_subclass_interface()
    {
        $this->assertFalse(
            TypeDescriptor::create(MessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::create(MessageHandlerBuilderWithParameterConverters::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_class_with_its_abstract_class()
    {
        $this->assertTrue(
            TypeDescriptor::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(TypeDescriptor::create(InputOutputMessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_void_with_void()
    {
        $this->assertFalse(
            TypeDescriptor::create(TypeDescriptor::VOID)->isCompatibleWith(TypeDescriptor::create(TypeDescriptor::VOID))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_compatibility_when_comparing_actual_class_to_object_type_hint()
    {
        $this->assertTrue(
            TypeDescriptor::create(\stdClass::class)->isCompatibleWith(TypeDescriptor::create(TypeDescriptor::OBJECT))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_no_compatibility_when_comparing_object_type_hint_to_actual_class()
    {
        $this->assertFalse(
            TypeDescriptor::create(TypeDescriptor::OBJECT)->isCompatibleWith(TypeDescriptor::create(\stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_false_type_resulting_in_boolean()
    {
        $this->assertEquals(
            TypeDescriptor::createBooleanType(),
            TypeDescriptor::create("false")
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_guessing_type_from_variable()
    {
        $this->assertEquals(TypeDescriptor::FLOAT, TypeDescriptor::createFromVariable(1.21));
        $this->assertEquals(TypeDescriptor::INTEGER, TypeDescriptor::createFromVariable(121));
        $this->assertEquals(TypeDescriptor::STRING, TypeDescriptor::createFromVariable("text"));
        $this->assertEquals(TypeDescriptor::ARRAY, TypeDescriptor::createFromVariable([]));
        $this->assertEquals(\stdClass::class, TypeDescriptor::createFromVariable(new \stdClass()));
        $this->assertEquals(TypeDescriptor::ARRAY, TypeDescriptor::createFromVariable([]));
        $this->assertEquals(TypeDescriptor::ARRAY, TypeDescriptor::createFromVariable([1,2,3]));
        $this->assertEquals(TypeDescriptor::ARRAY, TypeDescriptor::createFromVariable([new \stdClass(), 12]));
        $this->assertEquals(TypeDescriptor::ARRAY, TypeDescriptor::createFromVariable([new \stdClass(), OrderExample::createFromId(1)]));
        $this->assertEquals(TypeDescriptor::createCollection(\stdClass::class), TypeDescriptor::createFromVariable([new \stdClass()]));
        $this->assertEquals(TypeDescriptor::createCollection(\stdClass::class), TypeDescriptor::createFromVariable([new \stdClass(),new \stdClass()]));
        $this->assertEquals(TypeDescriptor::RESOURCE, TypeDescriptor::createFromVariable(fopen('file', 'w+')));
        $this->assertEquals(TypeDescriptor::NULL, TypeDescriptor::createFromVariable(null));
        $this->assertEquals(TypeDescriptor::CALLABLE, TypeDescriptor::createFromVariable(function (){}));
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_collection_type()
    {
        $this->assertEquals("array<stdClass>", TypeDescriptor::createCollection(\stdClass::class)->toString());
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_collection_type_with_two_generic_types()
    {
        $this->assertEquals(
            [TypeDescriptor::createStringType(), TypeDescriptor::createIntegerType()],
            TypeDescriptor::create("array<string,int>")->resolveGenericTypes()
        );
    }

    public function test_creating_for_boolean_with_full_name()
    {
        $this->assertEquals(
            TypeDescriptor::createBooleanType(),
            TypeDescriptor::create("boolean")
        );
    }

    public function test_creating_for_integer_with_full_name()
    {
        $this->assertEquals(
            TypeDescriptor::createIntegerType(),
            TypeDescriptor::create("integer")
        );
    }
}