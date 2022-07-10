<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Doctrine\Common\Annotations\AnnotationReader;
use Ecotone\Messaging\Config\Annotation\AutoloadFileNamespaceParser;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ClassPropertyDefinition;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\CollectionOfClassesFromDifferentNamespaceUsingGroupAlias;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\Description;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\ProductName;
use Test\Ecotone\Messaging\Fixture\Conversion\InCorrectArrayDocblock;
use Test\Ecotone\Messaging\Fixture\Conversion\PrivateRocketDetails\PrivateDetails;
use Test\Ecotone\Messaging\Fixture\Conversion\Product;
use Test\Ecotone\Messaging\Fixture\Conversion\PublicRocketDetails\PublicDetails;
use Test\Ecotone\Messaging\Fixture\Conversion\Rocket;
use Test\Ecotone\Messaging\Fixture\Conversion\User;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ArrayTypeWithDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ArrayTypeWithInlineDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\DifferentTypeAndDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ExtendedOrderPropertyExample;
use Test\Ecotone\Messaging\Fixture\Handler\Property\Extra\ExtraObject;
use Test\Ecotone\Messaging\Fixture\Handler\Property\OrderPropertyExample;
use Test\Ecotone\Messaging\Fixture\Handler\Property\OrderWithTraits;
use Test\Ecotone\Messaging\Fixture\Handler\Property\PropertyAnnotationExample;

/**
 * Class ClassDefinitionTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ClassDefinitionTest extends TestCase
{
    public function test_retrieving_public_class_property()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("id", TypeDescriptor::createIntegerType(), true, false, []),
            $classDefinition->getProperty("id")
        );
    }

    public function test_retrieving_property_annotations()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createProtected("reference", TypeDescriptor::createStringType(), true, true, [new PropertyAnnotationExample()]),
            $classDefinition->getProperty("reference")
        );
    }

    public function test_retrieving_property_with_annotation()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            [
                ClassPropertyDefinition::createProtected("reference", TypeDescriptor::createStringType(), true, true, [new PropertyAnnotationExample()]),
                ClassPropertyDefinition::createPrivate("someClass", TypeDescriptor::create(\stdClass::class), true, false, [new PropertyAnnotationExample()])
            ],
            $classDefinition->getPropertiesWithAnnotation(TypeDescriptor::create(PropertyAnnotationExample::class))
        );
    }

    public function test_retrieving_public_property()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPublic("extendedName", TypeDescriptor::createAnythingType(), true, false, []),
            $classDefinition->getProperty("extendedName")
        );
    }

    public function test_retrieving_type_property_if_not_array()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(DifferentTypeAndDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("integer", TypeDescriptor::createIntegerType(), false, false, []),
            $classDefinition->getProperty("integer")
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("unknown", TypeDescriptor::createAnythingType(), true, false, []),
            $classDefinition->getProperty("unknown")
        );
    }

    public function test_retrieving_type_from_docblock_when_array()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(ArrayTypeWithDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("data", TypeDescriptor::createCollection(\stdClass::class), false, false, []),
            $classDefinition->getProperty("data")
        );
    }

    public function test_retrieving_type_from_inline_docblock_when_array()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(ArrayTypeWithInlineDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("data", TypeDescriptor::createCollection(ExtraObject::class), false, false, []),
            $classDefinition->getProperty("data")
        );
    }

    public function test_retrieving_type_from_docblock_using_group_statement()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(CollectionOfClassesFromDifferentNamespaceUsingGroupAlias::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPublic("productDescriptions", TypeDescriptor::createCollection(Description::class), false, false, []),
            $classDefinition->getProperty("productDescriptions")
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPublic("productNames", TypeDescriptor::createCollection(ProductName::class), false, false, []),
            $classDefinition->getProperty("productNames")
        );
    }

    public function test_retrieving_annotations_from_parent_class_properties()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(ExtendedOrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createProtected("reference", TypeDescriptor::createStringType(), true, true, [new PropertyAnnotationExample()]),
            $classDefinition->getProperty("reference")
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("someClass", TypeDescriptor::create(\stdClass::class), true, false, [new PropertyAnnotationExample()]),
            $classDefinition->getProperty("someClass")
        );
    }

    public function test_retrieving_private_from_trait()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderWithTraits::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("property", TypeDescriptor::create(ExtraObject::class), true, false, [new AggregateIdentifier()]),
            $classDefinition->getProperty("property")
        );
    }

    public function test_retrieving_private_from_trait_inside_trait()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(Rocket::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("publicDetails", TypeDescriptor::create(PublicDetails::class), true, false, []),
            $classDefinition->getProperty("publicDetails")
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("privateDetails", TypeDescriptor::create(PrivateDetails::class), true, false, []),
            $classDefinition->getProperty("privateDetails")
        );
    }

    public function test_retrieving_typed_property()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("name", TypeDescriptor::createStringType(), false, false, []),
            $classDefinition->getProperty("name")
        );
    }

    public function test_retrieving_nullable_typed_property()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("quantity", TypeDescriptor::createIntegerType(), true, false, []),
            $classDefinition->getProperty("quantity")
        );
    }

    public function test_override_typed_property_with_annotation_type()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("owners", TypeDescriptor::create("array<Test\Ecotone\Messaging\Fixture\Conversion\Admin>"), false, false, []),
            $classDefinition->getProperty("owners")
        );
    }

    public function test_ignoring_docblock_when_is_incorrect()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(InCorrectArrayDocblock::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("incorrectProperty", TypeDescriptor::createArrayType(), false, false, []),
            $classDefinition->getProperty("incorrectProperty")
        );
    }
}