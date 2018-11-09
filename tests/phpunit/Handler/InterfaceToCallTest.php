<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler;
use Fixture\Conversion\AbstractSuperAdmin;
use Fixture\Conversion\Admin;
use Fixture\Conversion\Email;
use Fixture\Conversion\Extra\Favourite;
use Fixture\Conversion\Extra\Permission;
use Fixture\Conversion\OnlineShop;
use Fixture\Conversion\Password;
use Fixture\Conversion\SuperAdmin;
use Fixture\Conversion\User;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;

/**
 * Class InterfaceToCallTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_doc_block_guessing_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changePassword"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("password", TypeDescriptor::create("\\" . Password::class)),
            $interfaceToCall->getParameterWithName("password")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_than_class_it_self()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_using_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSingleFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create("\Fixture\Conversion\Extra\Favourite")),
            $interfaceToCall->getParameterWithName("favourite")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_transformation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "removeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_class_name_from_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "disableFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_compound_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "randomRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("random",TypeDescriptor::create( TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("random")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_first_type_hint_from_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addPhones"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("phones", TypeDescriptor::create(TypeDescriptor::ARRAY)),
            $interfaceToCall->getParameterWithName("phones")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_class_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addEmail"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("email", TypeDescriptor::create("\\" . Email::class)),
            $interfaceToCall->getParameterWithName("email")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_interface_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("productId", TypeDescriptor::create("\\" . \stdClass::class)),
            $interfaceToCall->getParameterWithName("productId")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
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

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_guessing_return_type_based_on_inherit_doc_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "findGames"
        );

        $this->assertTrue($interfaceToCall->doesItReturnIterable());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_unknown_if_no_type_hint_information_available()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSurname"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("surname", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("surname")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_choosing_unknown_type_if_mixed_type_hint_in_doc_block()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeAddress"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("address", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("address")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_choosing_declaring_class_if_use_this_or_self_or_static()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::create(User::class, "getSelf"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::create(User::class, "getStatic"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::create(User::class, "getSelfWithoutDocBlock"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_guessing_type_hint_with_full_qualified_name()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . User::class),
            (InterfaceToCall::create(User::class, "returnFullUser"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_guessing_type_hint_from_global_namespace()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . \stdClass::class),
            (InterfaceToCall::create(User::class, "returnFromGlobalNamespace"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_resolving_sub_class_for_static_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . SuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getSuperAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_interface()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . Admin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_abstract_class()
    {
        $this->assertEquals(
            TypeDescriptor::create("\\" . AbstractSuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getInformation"))->getReturnType()
        );
    }
}