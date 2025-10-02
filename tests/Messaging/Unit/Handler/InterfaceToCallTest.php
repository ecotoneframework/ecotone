<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use DateTimeInterface;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\Ecotone\Messaging\Fixture\Conversion\Admin;
use Test\Ecotone\Messaging\Fixture\Conversion\Email;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\LazyUser;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Permission;
use Test\Ecotone\Messaging\Fixture\Conversion\IgnoreDocblockClassLevel;
use Test\Ecotone\Messaging\Fixture\Conversion\InCorrectArrayDocblock;
use Test\Ecotone\Messaging\Fixture\Conversion\Password;
use Test\Ecotone\Messaging\Fixture\Conversion\SuperAdmin;
use Test\Ecotone\Messaging\Fixture\Conversion\TwoStepPassword;
use Test\Ecotone\Messaging\Fixture\Conversion\User;
use Test\Ecotone\Messaging\Fixture\Dto\MethodWithCallable;
use Test\Ecotone\Messaging\Fixture\Handler\ObjectWithConstructorProperties;

/**
 * Class InterfaceToCallTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class InterfaceToCallTest extends TestCase
{
    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changeName'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('name', Type::create(Type::STRING)),
            $interfaceToCall->getParameterWithName('name')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_doc_block_guessing_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changePassword'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('password', Type::create(Password::class)),
            $interfaceToCall->getParameterWithName('password')
        );
    }


    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changeDetails'
        );

        $this->assertEquals(
            InterfaceParameter::createNullable('details', Type::object('\\stdClass')->nullable()),
            $interfaceToCall->getParameterWithName('details')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_than_class_it_self()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changeFavourites'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('favourites', Type::create("array<Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName('favourites')
        );
    }

    public function test_retrieving_mixed_array_collection()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'mixedArrayCollection'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('data', Type::create('array')),
            $interfaceToCall->getParameterWithName('data')
        );

        $this->assertEquals(
            Type::create('array<mixed>'),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_retrieving_union_return_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'withUnionArrayReturnType'
        );

        $this->assertEquals(
            Type::create('array|string|int'),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_array_union_type_and_docblock()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'withUnionArrayReturnTypeWithDocblock'
        );

        $this->assertEquals(
            Type::array(Type::object(stdClass::class)),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_structured_array_collection_type_docblock()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'withStructuredArrayCollectionType'
        );

        $this->assertEquals(
            Type::array(Type::int(), Type::arrayShape(['person_id' => Type::string()])),
            $interfaceToCall->getParameterWithName('param')->getTypeDescriptor()
        );
    }

    public function test_structured_array_collection_type_docblock_return_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'withStructuredArrayCollectionReturnType'
        );

        $this->assertEquals(
            Type::array(Type::int(), Type::arrayShape(['person_id' => Type::string()])),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_using_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changeSingleFavourite'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('favourite', Type::create("\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite")),
            $interfaceToCall->getParameterWithName('favourite')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_transformation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'removeFavourites'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('favourites', Type::create("array<\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName('favourites')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_class_name_from_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'disableFavourites'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('favourites', Type::create("array<\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName('favourites')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_use_statement_alias()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'addAdminPermission'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('adminPermission', Type::create(Permission::class)),
            $interfaceToCall->getParameterWithName('adminPermission')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_of_scalar_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'addRatings'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('ratings', Type::create('array<int>')),
            $interfaceToCall->getParameterWithName('ratings')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_primitive_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'removeRating'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('rating', Type::int()),
            $interfaceToCall->getParameterWithName('rating')
        );
    }

    public function test_parsing_union_parameter_type_with_null()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'randomRating'
        );

        $this->assertEquals(
            InterfaceParameter::createNullable('random', Type::union(Type::array(), Type::int(), Type::null())),
            $interfaceToCall->getParameterWithName('random')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_guessing_parameter_first_type_hint_from_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'addPhones'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('phones', Type::create('array|array<string>')),
            $interfaceToCall->getParameterWithName('phones')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_class_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'addEmail'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('email', Type::create(Email::class . '|' . Favourite::class)),
            $interfaceToCall->getParameterWithName('email')
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_ignoring_docblock_type_hint_on_method_level()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'ignoreDocblockTypeHint'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('data', Type::array()),
            $interfaceToCall->getParameterWithName('data')
        );

        $this->assertEquals(
            Type::array(),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_ignoring_docblock_type_hint_on_class_level()
    {
        $interfaceToCall = InterfaceToCall::create(
            IgnoreDocblockClassLevel::class,
            'doSomething'
        );

        $this->assertEquals(
            Type::array(),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_guessing_parameter_from_inherit_doc_with_return_type_from_super_class_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            LazyUser::class,
            'bannedBy'
        );

        $this->assertEquals(
            Type::create(Admin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_guessing_interface_return_parameter_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'interfaceFromGlobalNamespace'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('dateTime', Type::create(DateTimeInterface::class)),
            $interfaceToCall->getParameterWithName('dateTime')
        );

        $this->assertEquals(
            Type::create(DateTimeInterface::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_resolving_return_type_type_hint_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class,
            'getLessFavourite'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('favourite', Type::create(Favourite::class)),
            $interfaceToCall->getParameterWithName('favourite')
        );
        $this->assertEquals(
            Type::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_resolving_callable_type_hint()
    {
        $interfaceToCall = InterfaceToCall::create(
            MethodWithCallable::class,
            'execute'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('closure', Type::callable()),
            $interfaceToCall->getParameterWithName('closure')
        );
        $this->assertEquals(
            Type::callable(),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_resolving_return_type_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class,
            'getYourVeryBestFavourite'
        );

        $this->assertEquals(
            InterfaceParameter::createNullable('favourite', Type::object(Favourite::class)->nullable()),
            $interfaceToCall->getParameterWithName('favourite')
        );
        $this->assertEquals(
            Type::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_overriding_trait_method_return_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class,
            'getUser'
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable('user', Type::create(SuperAdmin::class)),
            $interfaceToCall->getParameterWithName('user')
        );
        $this->assertEquals(
            Type::create(SuperAdmin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_guessing_unknown_if_no_type_hint_information_available()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class,
            'changeSurname'
        );

        $this->assertEquals(
            InterfaceParameter::createNullable('surname', Type::anything()),
            $interfaceToCall->getParameterWithName('surname')
        );
    }

    public function test_choosing_declaring_class_if_use_this_or_self_or_static()
    {
        $this->assertEquals(
            Type::create(User::class),
            (InterfaceToCall::create(User::class, 'getSelf'))->getReturnType()
        );

        $this->assertEquals(
            Type::create(User::class),
            (InterfaceToCall::create(User::class, 'getStatic'))->getReturnType()
        );

        $this->assertEquals(
            Type::create('array<' . User::class . '>'),
            (InterfaceToCall::create(User::class, 'getSelfArray'))->getReturnType()
        );

        $this->assertEquals(
            Type::create('array<' . User::class . '>'),
            (InterfaceToCall::create(User::class, 'getStaticArray'))->getReturnType()
        );
    }

    public function test_different_different_class_under_same_alias_than_in_declaring_one()
    {
        $this->assertEquals(
            Type::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, 'getPassword'))->getParameterWithName('password')->getTypeDescriptor()
        );
        $this->assertEquals(
            Type::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, 'getPassword'))->getReturnType()
        );
    }

    public function test_ignoring_parameter_docblock_type_hint_if_incorrect()
    {
        $this->assertEquals(
            Type::array(),
            (InterfaceToCall::create(InCorrectArrayDocblock::class, 'incorrectParameter'))->getParameterWithName('data')->getTypeDescriptor()
        );
    }

    public function test_ignoring_return_type_docblock_type_hint_if_incorrect()
    {
        $this->assertEquals(
            Type::array(),
            (InterfaceToCall::create(InCorrectArrayDocblock::class, 'incorrectReturnType'))->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_guessing_type_hint_with_full_qualified_name()
    {
        $this->assertEquals(
            Type::create(User::class),
            (InterfaceToCall::create(User::class, 'returnFullUser'))->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_guessing_type_hint_from_global_namespace()
    {
        $this->assertEquals(
            Type::create(stdClass::class),
            (InterfaceToCall::create(User::class, 'returnFromGlobalNamespace'))->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_interface()
    {
        $this->assertEquals(
            Type::anything(),
            (InterfaceToCall::create(SuperAdmin::class, 'getAdmin'))->getReturnType()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_abstract_class()
    {
        $this->assertEquals(
            Type::create(AbstractSuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, 'getInformation'))->getReturnType()
        );
    }

    public function test_retrieving_annotations()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, 'convert');

        $this->assertEquals(
            [new Converter()],
            $interfaceToCall->getMethodAnnotations()
        );
        $this->assertEquals(
            [new ClassReference('exampleConverterService')],
            $interfaceToCall->getClassAnnotations()
        );
    }

    public function test_retrieving_specific_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, 'convert');

        $this->assertTrue($interfaceToCall->hasMethodAnnotation(Type::create(Converter::class)));
        $this->assertFalse($interfaceToCall->hasMethodAnnotation(Type::create(ClassReference::class)));

        $this->assertTrue($interfaceToCall->hasClassAnnotation(Type::create(ClassReference::class)));

        $this->assertEquals(
            new ClassReference('exampleConverterService'),
            $interfaceToCall->getSingleClassAnnotationOf(Type::create(ClassReference::class))
        );
        $this->assertEquals(
            new Converter(),
            $interfaceToCall->getSingleMethodAnnotationOf(Type::create(Converter::class))
        );
    }

    public function test_throwing_exception_when_retrieving_not_existing_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, 'convert');

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getSingleMethodAnnotationOf(Type::create(ClassReference::class));
    }

    public function test_throwing_exception_when_retrieving_not_existing_class_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, 'convert');

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getSingleClassAnnotationOf(Type::create(Asynchronous::class));
    }

    public function test_not_throwing_exception_when_retrieving_constructor_parameter_attributes()
    {
        $interfaceToCall = InterfaceToCall::create(ObjectWithConstructorProperties::class, '__construct');

        $this->assertCount(1, $interfaceToCall->getFirstParameter()->getAnnotations());
    }
}
