<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler;
use Fixture\Conversion\Email;
use Fixture\Conversion\Extra\Favourite;
use Fixture\Conversion\Extra\Permission;
use Fixture\Conversion\Password;
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
            InterfaceParameter::create("name", TypeDescriptor::create("string", false, "")),
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
            InterfaceParameter::create("password", TypeDescriptor::create(Password::class, false, "\\" . Password::class)),
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
            InterfaceParameter::create("details", TypeDescriptor::create(TypeDescriptor::UNKNOWN, true, "\\stdClass")),
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
            InterfaceParameter::create("favourites", TypeDescriptor::create(TypeDescriptor::ARRAY, false, "array<Fixture\Conversion\Extra\Favourite>")),
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
            InterfaceParameter::create("favourite", TypeDescriptor::create(Favourite::class, false, "\Fixture\Conversion\Extra\Favourite")),
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
            InterfaceParameter::create("favourites", TypeDescriptor::create(TypeDescriptor::ARRAY, false, "array<\Fixture\Conversion\Extra\Favourite>")),
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
            InterfaceParameter::create("favourites", TypeDescriptor::create(TypeDescriptor::ARRAY, false, "array<\Fixture\Conversion\Extra\Favourite>")),
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
            InterfaceParameter::create("adminPermission", TypeDescriptor::create(Permission::class, false, "\\" . Permission::class)),
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
            InterfaceParameter::create("ratings", TypeDescriptor::create(TypeDescriptor::ITERABLE, false, "array<int>")),
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
            InterfaceParameter::create("rating", TypeDescriptor::create(TypeDescriptor::INTEGER, false, "int")),
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
            InterfaceParameter::create("random",TypeDescriptor::create( TypeDescriptor::UNKNOWN, true, "array")),
            $interfaceToCall->getParameterWithName("random")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_compound_and_collection()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addPhones"
        );

        $this->assertEquals(
            InterfaceParameter::create("phones", TypeDescriptor::create(TypeDescriptor::UNKNOWN, true, "array<string>")),
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
            InterfaceParameter::create("email", TypeDescriptor::create(TypeDescriptor::UNKNOWN, true, "\\" . Email::class)),
            $interfaceToCall->getParameterWithName("email")
        );
    }
}