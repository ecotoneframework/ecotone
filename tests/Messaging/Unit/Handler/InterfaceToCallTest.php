<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler;
use SimplyCodedSoftware\Messaging\Annotation\Converter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Admin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Email;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Permission;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\OnlineShop;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Password;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\SuperAdmin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\User;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class InterfaceToCallTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeName"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("name", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("name")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_doc_block_guessing_namespace()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changePassword"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("password", TypeDescriptor::create("\\" . Password::class)),
            $interfaceToCall->getParameterWithName("password")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeDetails"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("details", TypeDescriptor::create("\\stdClass")),
            $interfaceToCall->getParameterWithName("details")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_than_class_it_self()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_using_use_statements()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeSingleFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create("\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite")),
            $interfaceToCall->getParameterWithName("favourite")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_transformation()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "removeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_class_name_from_use_statements()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "disableFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_use_statement_alias()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "addAdminPermission"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("adminPermission", TypeDescriptor::create(Permission::class)),
            $interfaceToCall->getParameterWithName("adminPermission")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_of_scalar_type()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "addRatings"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("ratings", TypeDescriptor::create("array<int>")),
            $interfaceToCall->getParameterWithName("ratings")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_primitive_type()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "removeRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("rating", TypeDescriptor::create(TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("rating")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_compound_type()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "randomRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("random",TypeDescriptor::create( TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("random")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_first_type_hint_from_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "addPhones"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("phones", TypeDescriptor::create(TypeDescriptor::ARRAY)),
            $interfaceToCall->getParameterWithName("phones")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_class_name()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "addEmail"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("email", TypeDescriptor::create("\\" . Email::class)),
            $interfaceToCall->getParameterWithName("email")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_interface_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("productId", TypeDescriptor::create("\\" . \stdClass::class)),
            $interfaceToCall->getParameterWithName("productId")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_abstract_class_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            OnlineShop::class, "findGames"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("gameId", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("gameId")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_return_type_based_on_inherit_doc_annotation()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            OnlineShop::class, "findGames"
        );

        $this->assertTrue($interfaceToCall->doesItReturnIterable());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_unknown_if_no_type_hint_information_available()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeSurname"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("surname", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("surname")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_choosing_unknown_type_if_mixed_type_hint_in_doc_block()
    {
        $interfaceToCall = InterfaceToCall::createWithoutCaching(
            User::class, "changeAddress"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("address", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("address")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_declaring_class_if_use_this_or_self_or_static()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::createWithoutCaching(User::class, "getSelf"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::createWithoutCaching(User::class, "getStatic"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::createWithoutCaching(User::class, "getSelfWithoutDocBlock"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_with_full_qualified_name()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::createWithoutCaching(User::class, "returnFullUser"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_global_namespace()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . \stdClass::class),
            (InterfaceToCall::createWithoutCaching(User::class, "returnFromGlobalNamespace"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_sub_class_for_static_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . SuperAdmin::class),
            (InterfaceToCall::createWithoutCaching(SuperAdmin::class, "getSuperAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_interface()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . Admin::class),
            (InterfaceToCall::createWithoutCaching(SuperAdmin::class, "getAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_abstract_class()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . AbstractSuperAdmin::class),
            (InterfaceToCall::createWithoutCaching(SuperAdmin::class, "getInformation"))->getReturnType()
        );
    }

    public function test_retrieving_annotations()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert", InMemoryAnnotationRegistrationService::createFrom([ExampleConverterService::class]));

        $this->assertEquals(
            [new Converter()],
            $interfaceToCall->getMethodAnnotations()
        );
        $this->assertEquals(
            [new MessageEndpoint()],
            $interfaceToCall->getClassAnnotations()
        );
    }

    public function test_retrieving_specific_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert", InMemoryAnnotationRegistrationService::createFrom([ExampleConverterService::class]));

        $this->assertTrue($interfaceToCall->hasMethodAnnotation(Converter::class));
        $this->assertFalse($interfaceToCall->hasMethodAnnotation(MessageEndpoint::class));

        $this->assertTrue($interfaceToCall->hasClassAnnotation(MessageEndpoint::class));
        $this->assertFalse($interfaceToCall->hasClassAnnotation(Converter::class));

        $this->assertEquals(
            new MessageEndpoint(),
            $interfaceToCall->getClassAnnotation(MessageEndpoint::class)
        );
        $this->assertEquals(
            new Converter(),
            $interfaceToCall->getMethodAnnotation(Converter::class)
        );
    }

    public function test_throwing_exception_when_retrieving_not_existing_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert", InMemoryAnnotationRegistrationService::createFrom([ExampleConverterService::class]));

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getMethodAnnotation(MessageEndpoint::class);
    }

    public function test_throwing_exception_when_retrieving_not_existing_class_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert", InMemoryAnnotationRegistrationService::createFrom([ExampleConverterService::class]));

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getClassAnnotation(Converter::class);
    }
}