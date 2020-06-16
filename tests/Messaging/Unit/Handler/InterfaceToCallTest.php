<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\ConverterClass;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Fixture\Conversion\Product;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\Ecotone\Messaging\Fixture\Conversion\Admin;
use Test\Ecotone\Messaging\Fixture\Conversion\Email;
use Test\Ecotone\Messaging\Fixture\Conversion\ExampleTestAnnotation;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\LazyUser;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Permission;
use Test\Ecotone\Messaging\Fixture\Conversion\InCorrectInterfaceExample;
use Test\Ecotone\Messaging\Fixture\Conversion\OnlineShop;
use Test\Ecotone\Messaging\Fixture\Conversion\Password;
use Test\Ecotone\Messaging\Fixture\Conversion\SuperAdmin;
use Test\Ecotone\Messaging\Fixture\Conversion\TwoStepPassword;
use Test\Ecotone\Messaging\Fixture\Conversion\User;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class InterfaceToCallTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeName"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("name", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("name")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_doc_block_guessing_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changePassword"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("password", TypeDescriptor::create(Password::class)),
            $interfaceToCall->getParameterWithName("password")
        );
    }



    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeDetails"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("details", TypeDescriptor::create("\\stdClass")),
            $interfaceToCall->getParameterWithName("details")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_than_class_it_self()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    public function test_retrieving_mixed_array_collection()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "mixedArrayCollection"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("data", TypeDescriptor::create("array")),
            $interfaceToCall->getParameterWithName("data")
        );

        $this->assertEquals(
            TypeDescriptor::create("array"),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_using_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSingleFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create("\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite")),
            $interfaceToCall->getParameterWithName("favourite")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_transformation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "removeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_class_name_from_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "disableFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_use_statement_alias()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addAdminPermission"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("adminPermission", TypeDescriptor::create(Permission::class)),
            $interfaceToCall->getParameterWithName("adminPermission")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_of_scalar_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addRatings"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("ratings", TypeDescriptor::create("array<int>")),
            $interfaceToCall->getParameterWithName("ratings")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_primitive_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "removeRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("rating", TypeDescriptor::create(TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("rating")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_compound_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "randomRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("random",TypeDescriptor::create( TypeDescriptor::INTEGER . "|" . TypeDescriptor::ARRAY)),
            $interfaceToCall->getParameterWithName("random")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_first_type_hint_from_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addPhones"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("phones", TypeDescriptor::create( "array|array<string>")),
            $interfaceToCall->getParameterWithName("phones")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_class_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addEmail"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("email", TypeDescriptor::create(Email::class . "|" . Favourite::class)),
            $interfaceToCall->getParameterWithName("email")
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_interface_inherit_doc_with_different_parameter_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("productId", TypeDescriptor::create(\stdClass::class . "|" . TypeDescriptor::OBJECT)),
            $interfaceToCall->getParameterWithName("productId")
        );
    }

    public function test_retrieving_annotations_from_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            [new ExampleTestAnnotation()],
            $interfaceToCall->getMethodAnnotations()
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_ignoring_docblock_type_hint()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "ignoreDocblockTypeHint"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("data", TypeDescriptor::createArrayType()),
            $interfaceToCall->getParameterWithName("data")
        );

        $this->assertEquals(
            TypeDescriptor::createArrayType(),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_inherit_doc_with_return_type_from_super_class_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            LazyUser::class, "bannedBy"
        );

        $this->assertEquals(
            TypeDescriptor::create(Admin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_interface_return_parameter_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "interfaceFromGlobalNamespace"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("dateTime", TypeDescriptor::create(\DateTimeInterface::class)),
            $interfaceToCall->getParameterWithName("dateTime")
        );

        $this->assertEquals(
            TypeDescriptor::create(\DateTimeInterface::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_abstract_class_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "findGames"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("gameId", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("gameId")
        );
    }

    public function test_resolving_return_type_type_hint_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getLessFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create(Favourite::class)),
            $interfaceToCall->getParameterWithName("favourite")
        );
        $this->assertEquals(
            TypeDescriptor::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_resolving_return_type_type_hint_located_in_docblock_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getYourVeryBestFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("favourite", TypeDescriptor::create(Favourite::class)),
            $interfaceToCall->getParameterWithName("favourite")
        );
        $this->assertEquals(
            TypeDescriptor::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_overriding_trait_method_return_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getUser"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("user", TypeDescriptor::create(SuperAdmin::class)),
            $interfaceToCall->getParameterWithName("user")
        );
        $this->assertEquals(
            TypeDescriptor::create(SuperAdmin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_guessing_return_type_based_on_inherit_doc_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "findGames"
        );

        $this->assertTrue($interfaceToCall->doesItReturnIterable());
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_unknown_if_no_type_hint_information_available()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSurname"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("surname", TypeDescriptor::create(TypeDescriptor::ANYTHING)),
            $interfaceToCall->getParameterWithName("surname")
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_choosing_unknown_type_if_mixed_type_hint_in_doc_block()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeAddress"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("address", TypeDescriptor::create(TypeDescriptor::ANYTHING)),
            $interfaceToCall->getParameterWithName("address")
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_choosing_declaring_class_if_use_this_or_self_or_static()
    {
        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getSelf"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getStatic"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getSelfWithoutDocBlock"))->getReturnType()
        );
    }

    public function test_different_different_class_under_same_alias_than_in_declaring_one()
    {
        $this->assertEquals(
            TypeDescriptor::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, "getPassword"))->getParameterWithName("password")->getTypeDescriptor()
        );
        $this->assertEquals(
            TypeDescriptor::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, "getPassword"))->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_guessing_type_hint_with_full_qualified_name()
    {
        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "returnFullUser"))->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_global_namespace()
    {
        $this->assertEquals(
            TypeDescriptor::create(\stdClass::class),
            (InterfaceToCall::create(User::class, "returnFromGlobalNamespace"))->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_resolving_sub_class_for_static_type_hint_return_type()
    {
        $this->assertEquals(
            TypeDescriptor::create(SuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getSuperAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_interface()
    {
        $this->assertEquals(
            TypeDescriptor::create(Admin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_abstract_class()
    {
        $this->assertEquals(
            TypeDescriptor::create(AbstractSuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getInformation"))->getReturnType()
        );
    }

    public function test_retrieving_annotations()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->assertEquals(
            [new Converter()],
            $interfaceToCall->getMethodAnnotations()
        );
        $this->assertEquals(
            [new ConverterClass()],
            $interfaceToCall->getClassAnnotations()
        );
    }

    public function test_retrieving_specific_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->assertTrue($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(Converter::class)));
        $this->assertFalse($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(MessageEndpoint::class)));

        $this->assertTrue($interfaceToCall->hasClassAnnotation(TypeDescriptor::create(ConverterClass::class)));

        $this->assertEquals(
            new ConverterClass(),
            $interfaceToCall->getClassAnnotation(TypeDescriptor::create(ConverterClass::class))
        );
        $this->assertEquals(
            new Converter(),
            $interfaceToCall->getMethodAnnotation(TypeDescriptor::create(Converter::class))
        );
    }

    public function test_throwing_exception_when_retrieving_not_existing_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getMethodAnnotation(TypeDescriptor::create(MessageEndpoint::class));
    }

    public function test_throwing_exception_when_retrieving_not_existing_class_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getClassAnnotation(TypeDescriptor::create(Asynchronous::class));
    }

    public function test_throwing_when_doc_block_has_return_value_and_declaration_has_void()
    {
        $this->expectException(InvalidArgumentException::class);

        InterfaceToCall::create(InCorrectInterfaceExample::class, "voidWithReturnValue");
    }
}