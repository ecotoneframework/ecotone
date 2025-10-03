<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Closure;
use Countable;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\Type\ArrayShapeType;
use Ecotone\Messaging\Handler\Type\TypeContext;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Dto\OrderExample;
use Test\Ecotone\Messaging\Fixture\Handler\DumbMessageHandlerBuilder;
use Traversable;

/**
 * Class TypeDescriptorTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class TypeDescriptorTest extends TestCase
{
    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_guessing_type_hint_from_compound_type_and_array_of_scalar_type()
    {
        $typeDescription = Type::createWithDocBlock('array', 'array<string>');

        $this->assertEquals(
            'array<string>',
            $typeDescription->toString()
        );
    }

    public function test_is_non_class_collection()
    {
        $this->assertFalse(Type::create('string')->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::create('array')->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::iterable()->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::create('array<string>')->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::create('array<string, string>')->isArrayButNotClassBasedCollection());
        $this->assertFalse(Type::create('array<\stdClass>')->isArrayButNotClassBasedCollection());
        $this->assertFalse(Type::create('array<string, \stdClass>')->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::create('array<array<string,int>>')->isArrayButNotClassBasedCollection());
        $this->assertTrue(Type::create('array<string, array<string,int>>')->isArrayButNotClassBasedCollection());
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_guessing_type_hint_from_null()
    {
        $this->assertEquals(
            'null',
            Type::create('null')->getTypeHint()
        );
    }

    public function test_guessing_unknown_type_if_only_empty_strings_passed()
    {
        $this->assertEquals(
            'mixed',
            Type::createWithDocBlock('    ', '    ')
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_returning_base_type_when_docblock_is_incorrect()
    {
        $this->assertEquals(
            Type::array(),
            Type::createWithDocBlock('array', 'array<bla>')
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_type_hint_is_incorrect()
    {
        $this->expectException(TypeDefinitionException::class);

        Type::create('bla');
    }

    //    /**
    //     * @throws TypeDefinitionException
    //     * @throws MessagingException
    //     */
    //    public function test_passing_incompatible_resource_type_hint_and_scalar_union_type()
    //    {
    //        $this->expectException(TypeDefinitionException::class);
    //
    //        TypeDescriptor::create(TypeDescriptor::RESOURCE.'|'.TypeDescriptor::INTEGER);
    //    }
    //
    //    /**
    //     * @throws TypeDefinitionException
    //     * @throws MessagingException
    //     */
    //    public function test_passing_incompatible_resource_hint_and_compound_union_type()
    //    {
    //        $this->expectException(TypeDefinitionException::class);
    //
    //        TypeDescriptor::create(TypeDescriptor::RESOURCE.'|'.  TypeDescriptor::ARRAY);
    //    }
    //
    //    /**
    //     * @throws TypeDefinitionException
    //     * @throws MessagingException
    //     */
    //    public function test_passing_incompatible_compound_hint_and_resource_union_type()
    //    {
    //        $this->expectException(TypeDefinitionException::class);
    //
    //        TypeDescriptor::create(TypeDescriptor::ITERABLE.'|'.  TypeDescriptor::RESOURCE);
    //    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_converting_doc_block_array_type_to_generic()
    {
        $this->assertEquals(
            'array<stdClass>',
            Type::createWithDocBlock('iterable', "\stdClass[]")->getTypeHint()
        );
    }

    public function test_when_parameter_is_array_accepting_only_array_like_type_from_docblock()
    {
        $this->assertEquals(
            'array<stdClass>',
            Type::createWithDocBlock('array', "\stdClass|array<\stdClass>|int")->toString()
        );
    }

    public function test_when_parameter_is_union_with_array_accepting_only_array_like_type_from_docblock()
    {
        $this->assertEquals(
            'array<stdClass>',
            Type::createWithDocBlock('array' . '|' . stdClass::class, "string|array<\stdClass>|int")->toString()
        );
    }

    public function test_ignoring_docblock_if_iterable_or_array_when_declaration_is_array()
    {
        $this->assertEquals(
            'array',
            Type::createWithDocBlock('array', 'array|iterable')->toString()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_choosing_doc_block_type_hint_over_array()
    {
        $this->assertEquals(
            'array<stdClass>',
            Type::createWithDocBlock('array', "array<\stdClass>")->getTypeHint()
        );
    }

    public function test_choosing_doc_block_collection_type_hint_over_compound()
    {
        $typeDescriptor = Type::createWithDocBlock('iterable', "\Traversable<\stdClass>");

        $this->assertEquals(
            'Traversable<stdClass>',
            $typeDescriptor->getTypeHint()
        );

        $this->assertEquals(
            Type::object(Traversable::class, Type::object(stdClass::class)),
            $typeDescriptor
        );
    }

    public function test_choosing_native_type_if_docblock_does_not_exists()
    {
        $typeDescriptor = Type::createWithDocBlock('iterable', "\ANonExistentClass<\stdClass>");

        $this->assertEquals(
            Type::iterable(),
            $typeDescriptor
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_checking_equality()
    {
        $this->assertTrue(
            Type::create(Type::STRING)
                ->equals(Type::create(Type::STRING))
        );

        $this->assertTrue(
            Type::createWithDocBlock('iterable', "\stdClass[]")
                ->equals(Type::create("array<\stdClass>"))
        );

        $this->assertFalse(
            Type::object()
                ->equals(Type::int())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_choosing_declaration_type_over_docblock_when_interface()
    {
        $this->assertEquals(
            Countable::class,
            Type::createWithDocBlock(Countable::class, stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_choosing_docblock_when_object_type()
    {
        $this->assertEquals(
            Type::object(stdClass::class),
            Type::createWithDocBlock('object', stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_not_ignoring_docblock_if_not_property_is_not_iterable()
    {
        $this->assertEquals(
            Type::array(),
            Type::createWithDocBlock('mixed', 'array')->getTypeHint()
        );
    }

    public function test_choosing_declaration_array_type_over_unknown_docblock_type()
    {
        $this->assertEquals(
            Type::array(),
            Type::createWithDocBlock('array', 'mixed')->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_with_prefixed_type()
    {
        $this->assertEquals(
            stdClass::class,
            Type::create("\stdClass")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_with_compound_object_type_hint()
    {
        $this->assertEquals(
            'object',
            Type::create('object')->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_for_void_return_type_hint()
    {
        $this->assertEquals(
            'void',
            Type::create('void')->getTypeHint()
        );
    }

    public function test_is_interface()
    {
        $this->assertTrue(Type::object(MessageHandler::class)->isInterface());
        $this->assertFalse(Type::object(DumbMessageHandlerBuilder::class)->isInterface());
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_with_mixed_type_result_in_unknown_type_hint()
    {
        $this->assertEquals(
            Type::anything(),
            Type::createWithDocBlock('mixed', 'mixed')->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_anything_with_anything()
    {
        $this->assertTrue(
            Type::anything()->isCompatibleWith(Type::anything())
        );
    }

    public function test_no_compatibility_when_comparing_scalar_to_object_type()
    {
        $this->assertFalse(Type::string()->isCompatibleWith(Type::create('object')));
        $this->assertFalse(Type::create('object')->isCompatibleWith(Type::string()));
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_class_of_the_same_type()
    {
        $this->assertTrue(
            Type::create(stdClass::class)->isCompatibleWith(Type::create(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_class_with_scalar()
    {
        $this->assertFalse(
            Type::create(stdClass::class)->isCompatibleWith(Type::int())
        );

        $this->assertFalse(
            Type::int()->isCompatibleWith(Type::create(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_class_with_compound()
    {
        $this->assertFalse(
            Type::create(stdClass::class)->isCompatibleWith(Type::array())
        );

        $this->assertFalse(
            Type::array()->isCompatibleWith(Type::create(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_different_collections()
    {
        $this->assertFalse(
            Type::createCollection(stdClass::class)->isCompatibleWith(Type::createCollection(Message::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_same_collections()
    {
        $this->assertTrue(
            Type::createCollection(stdClass::class)->isCompatibleWith(Type::createCollection(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_collection_different_types()
    {
        $this->assertFalse(
            Type::createCollection('string')->isCompatibleWith(Type::createCollection(Uuid::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_scalar_with_compound()
    {
        $this->assertFalse(
            Type::array()->isCompatibleWith(Type::int())
        );

        $this->assertFalse(
            Type::int()->isCompatibleWith(Type::array())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_scalar_with_compound()
    {
        $this->assertFalse(
            Type::array()->isCompatibleWith(Type::int())
        );

        $this->assertFalse(
            Type::int()->isCompatibleWith(Type::array())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_scalar_with_object()
    {
        $this->assertFalse(
            Type::create(stdClass::class)->isCompatibleWith(Type::int())
        );

        $this->assertFalse(
            Type::int()->isCompatibleWith(Type::create(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_scalar_with_object_containing_to_string_method()
    {
        $this->assertTrue(Type::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(Type::string()));

        $this->assertFalse(Type::string()->isCompatibleWith(Type::create(DumbMessageHandlerBuilder::class)));
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_different_classes()
    {
        $this->assertFalse(
            Type::create(stdClass::class)->isCompatibleWith(Type::create(DumbMessageHandlerBuilder::class))
        );

        $this->assertFalse(
            Type::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(Type::create(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_class_and_its_interface()
    {
        $this->assertTrue(
            Type::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(Type::create(MessageHandlerBuilder::class))
        );

        $this->assertFalse(
            Type::create(MessageHandlerBuilder::class)->isCompatibleWith(Type::create(DumbMessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_subclass_interface_with_base_interface()
    {
        $this->assertTrue(
            Type::create(MessageHandlerBuilderWithParameterConverters::class)->isCompatibleWith(Type::create(MessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_interface_with_subclass_interface()
    {
        $this->assertFalse(
            Type::create(MessageHandlerBuilder::class)->isCompatibleWith(Type::create(MessageHandlerBuilderWithParameterConverters::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_class_with_its_abstract_class()
    {
        $this->assertTrue(
            Type::create(DumbMessageHandlerBuilder::class)->isCompatibleWith(Type::create(InputOutputMessageHandlerBuilder::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_void_with_void()
    {
        $this->assertFalse(
            Type::void()->isCompatibleWith(Type::void())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_compatibility_when_comparing_actual_class_to_object_type_hint()
    {
        $this->assertTrue(
            Type::object(stdClass::class)->isCompatibleWith(Type::object())
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_no_compatibility_when_comparing_object_type_hint_to_actual_class()
    {
        $this->assertFalse(
            Type::object()->isCompatibleWith(Type::object(stdClass::class))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_with_false_type_resulting_in_false()
    {
        $this->assertEquals(
            Type::false(),
            Type::create('false')
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_guessing_type_from_variable()
    {
        $this->assertEquals(Type::float(), Type::createFromVariable(1.21));
        $this->assertEquals(Type::int(), Type::createFromVariable(121));
        $this->assertEquals(Type::string(), Type::createFromVariable('text'));
        $this->assertEquals(Type::array(), Type::createFromVariable([]));
        $this->assertEquals(Type::object(stdClass::class), Type::createFromVariable(new stdClass()));
        $this->assertEquals(Type::array(), Type::createFromVariable([]));
        $this->assertEquals(Type::array(Type::int()), Type::createFromVariable([1, 2, 3]));
        $this->assertEquals(Type::create('array<string, int>'), Type::createFromVariable(['bla' => 1, 'bla2' => 2, 'bla3' => 3]));
        $this->assertEquals(Type::array(), Type::createFromVariable([new stdClass(), 12]));
        $this->assertEquals(Type::array(), Type::createFromVariable([new stdClass(), OrderExample::createFromId(1)]));
        $this->assertEquals(Type::createCollection(stdClass::class), Type::createFromVariable([new stdClass()]));
        $this->assertEquals(Type::createCollection(stdClass::class), Type::createFromVariable([new stdClass(), new stdClass()]));
        $this->assertEquals(Type::resource(), Type::createFromVariable(fopen('file', 'w+')));
        $this->assertEquals(Type::null(), Type::createFromVariable(null));
        $this->assertEquals(Closure::class, Type::createFromVariable(function () {})->toString());
        $this->assertEquals('array<array<string,int>>', Type::createFromVariable([['bla' => 1, 'bla2' => 2, 'bla3' => 3]])->toString());
        $this->assertEquals('array<string,null>', Type::createFromVariable(['test' => null])->toString());
        $this->assertEquals('array<string,mixed>', Type::createFromVariable(['test' => null, 'test2' => '123'])->toString());
    }

    public function test_resolving_structured_array_type()
    {
        $expectedArrayShape = new ArrayShapeType(['person_id' => Type::string()]);
        $this->assertEquals(
            Type::array(Type::int(), $expectedArrayShape),
            Type::create('array<int, array{person_id: string}>')
        );
        $this->assertEquals($expectedArrayShape, Type::create('array{person_id: string}'));
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_collection_type()
    {
        $this->assertEquals('array<stdClass>', Type::createCollection(stdClass::class)->toString());
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_creating_collection_type_with_two_generic_types()
    {
        $this->assertEquals(
            Type::array(Type::string(), Type::int()),
            Type::create('array<string,int>')
        );
    }

    public function test_creating_collection_type_with_nested_generic_types()
    {
        $this->assertEquals(
            Type::array(Type::string(), Type::array(Type::int())),
            Type::create('array<string,array<int>>')
        );
    }

    public function test_creating_for_boolean_with_full_name()
    {
        $this->assertEquals(
            Type::boolean(),
            Type::create('boolean')
        );
    }

    public function test_creating_for_integer_with_full_name()
    {
        $this->assertEquals(
            Type::int(),
            Type::create('integer')
        );
    }

    public function test_expanding_keywords_with_context(): void
    {
        $typeContext = TypeContext::forClass($this::class);
        $this->assertEquals(Type::object($this::class), Type::create('self', $typeContext));
        $this->assertEquals(Type::object($this::class), Type::create('static', $typeContext));

        $typeContext = TypeContext::forClass(DumbMessageHandlerBuilder::class, InputOutputMessageHandlerBuilder::class);
        $this->assertEquals(Type::object(InputOutputMessageHandlerBuilder::class), Type::create('parent', $typeContext));
    }

    public function test_throwing_exception_when_trying_to_expand_keywords_without_context(): void
    {
        $this->expectException(TypeDefinitionException::class);
        Type::create('self');
    }

    public function test_expanding_full_classnames(): void
    {
        $context = TypeContext::forClass(
            DumbMessageHandlerBuilder::class,
            namespace: 'Test\Ecotone\Messaging\Fixture\Handler',
            aliases: ['AnAlias' => $this::class]
        );
        $this->assertEquals(Type::object($this::class), Type::create('AnAlias', $context));
        $this->assertEquals(Type::object(DumbMessageHandlerBuilder::class), Type::create('DumbMessageHandlerBuilder', $context));
        $this->assertEquals(Type::object(stdClass::class), Type::create('stdClass', $context));
    }

    public function test_expanding_optional_token(): void
    {
        $this->assertEquals(
            Type::union(Type::null(), Type::string()),
            Type::create('?string')
        );
    }

    public function test_expanding_optional_token_with_union_type(): void
    {
        // This does not comply with PHP syntax but is allowed in docblocks ?
        $this->assertEquals(
            Type::union(Type::null(), Type::string(), Type::array()),
            Type::create('?string|array')
        );
    }

    public function test_it_can_parse_a_type_with_a_description(): void
    {
        // This does not comply with PHP syntax but is allowed in docblocks ?
        $this->assertEquals(
            Type::array(Type::string(), Type::int()),
            Type::create('array<string, int> A description')
        );
    }

    public function test_it_use_docblock_if_type_is_incompatible(): void
    {
        $this->assertEquals(
            Type::array(Type::object('stdClass')),
            Type::createWithDocBlock('array|int', 'stdClass[]')
        );
    }

    public function test_it_ignores_incomplete_array_shapes(): void
    {
        $this->assertEquals(Type::array(), Type::create('array{'));
        $this->assertEquals(Type::array(), Type::create('array{0'));
        $this->assertEquals(Type::array(), Type::create('array{0:'));
        $this->assertEquals(Type::array(), Type::create('array{0:i'));
    }
}
