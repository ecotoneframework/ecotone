<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ClassPropertyDefinition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingSaga;
use Ecotone\Modelling\Attribute\Identifier;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\CollectionOfClassesFromDifferentNamespaceUsingGroupAlias;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\Description;
use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\ProductName;
use Test\Ecotone\Messaging\Fixture\Conversion\InCorrectArrayDocblock;
use Test\Ecotone\Messaging\Fixture\Conversion\PrivateRocketDetails\PrivateDetails;
use Test\Ecotone\Messaging\Fixture\Conversion\Product;
use Test\Ecotone\Messaging\Fixture\Conversion\PublicRocketDetails\PublicDetails;
use Test\Ecotone\Messaging\Fixture\Conversion\Rocket;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ArrayTypeWithDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ArrayTypeWithInlineDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\DifferentTypeAndDocblockProperty;
use Test\Ecotone\Messaging\Fixture\Handler\Property\ExtendedOrderPropertyExample;
use Test\Ecotone\Messaging\Fixture\Handler\Property\Extra\ExtraObject;
use Test\Ecotone\Messaging\Fixture\Handler\Property\OrderPropertyExample;
use Test\Ecotone\Messaging\Fixture\Handler\Property\OrderWithTraits;
use Test\Ecotone\Messaging\Fixture\Handler\Property\PropertyAnnotationExample;
use Test\Ecotone\Messaging\Fixture\Handler\Property\PropertyAnnotationExampleBaseClasss;
use Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\Logger;

/**
 * Class ClassDefinitionTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ClassDefinitionTest extends TestCase
{
    public function test_retrieving_public_class_property()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('id', Type::int(), true, false, []),
            $classDefinition->getProperty('id')
        );
    }

    public function test_checking_if_has_annotation()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Logger::class));

        $this->assertTrue($classDefinition->hasClassAnnotation(Type::create(EventSourcingAggregate::class)));
        $this->assertTrue($classDefinition->hasClassAnnotation(Type::create(Aggregate::class)));
        $this->assertFalse($classDefinition->hasClassAnnotation(Type::create(EventSourcingSaga::class)));
    }

    public function test_retrieving_property_annotations()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createProtected('reference', Type::string(), true, true, [new PropertyAnnotationExample()]),
            $classDefinition->getProperty('reference')
        );
    }

    public function test_retrieving_class_annotations()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Logger::class));

        $this->assertEquals(
            new EventSourcingAggregate(),
            $classDefinition->getSingleClassAnnotation(Type::create(EventSourcingAggregate::class))
        );
        $this->assertEquals(
            new EventSourcingAggregate(),
            $classDefinition->getSingleClassAnnotation(Type::create(Aggregate::class))
        );
    }

    public function test_retrieving_property_with_annotation()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(OrderPropertyExample::class));

        $this->assertEquals(
            [
                ClassPropertyDefinition::createProtected('reference', Type::string(), true, true, [new PropertyAnnotationExample()]),
            ],
            $classDefinition->getPropertiesWithAnnotation(Type::create(PropertyAnnotationExample::class))
        );

        $this->assertEquals(
            [
                ClassPropertyDefinition::createProtected('reference', Type::string(), true, true, [new PropertyAnnotationExample()]),
                ClassPropertyDefinition::createPrivate('someClass', Type::create(stdClass::class), true, false, [new PropertyAnnotationExampleBaseClasss()]),
            ],
            $classDefinition->getPropertiesWithAnnotation(Type::create(PropertyAnnotationExampleBaseClasss::class))
        );
    }

    public function test_retrieving_public_property()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPublic('extendedName', Type::anything(), true, false, []),
            $classDefinition->getProperty('extendedName')
        );
    }

    public function test_retrieving_type_property_if_not_array()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(DifferentTypeAndDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('integer', Type::int(), false, false, []),
            $classDefinition->getProperty('integer')
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('unknown', Type::object(stdClass::class), true, false, []),
            $classDefinition->getProperty('unknown')
        );
    }

    public function test_retrieving_type_from_docblock_when_array()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(ArrayTypeWithDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('data', Type::createCollection(stdClass::class), false, false, []),
            $classDefinition->getProperty('data')
        );
    }

    public function test_retrieving_type_from_inline_docblock_when_array()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(ArrayTypeWithInlineDocblockProperty::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('data', Type::createCollection(ExtraObject::class), false, false, []),
            $classDefinition->getProperty('data')
        );
    }

    public function test_retrieving_type_from_docblock_using_group_statement()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(CollectionOfClassesFromDifferentNamespaceUsingGroupAlias::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPublic('productDescriptions', Type::createCollection(Description::class), false, false, []),
            $classDefinition->getProperty('productDescriptions')
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPublic('productNames', Type::createCollection(ProductName::class), false, false, []),
            $classDefinition->getProperty('productNames')
        );
    }

    public function test_retrieving_annotations_from_parent_class_properties()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(ExtendedOrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createProtected('reference', Type::string(), true, true, [new PropertyAnnotationExample()]),
            $classDefinition->getProperty('reference')
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('someClass', Type::create(stdClass::class), true, false, [new PropertyAnnotationExampleBaseClasss()]),
            $classDefinition->getProperty('someClass')
        );
    }

    public function test_retrieving_private_from_trait()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(OrderWithTraits::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('property', Type::create(ExtraObject::class), true, false, [new Identifier()]),
            $classDefinition->getProperty('property')
        );
    }

    public function test_retrieving_private_from_trait_inside_trait()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Rocket::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('publicDetails', Type::create(PublicDetails::class), true, false, []),
            $classDefinition->getProperty('publicDetails')
        );
        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('privateDetails', Type::create(PrivateDetails::class), true, false, []),
            $classDefinition->getProperty('privateDetails')
        );
    }

    public function test_retrieving_typed_property()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('name', Type::string(), false, false, []),
            $classDefinition->getProperty('name')
        );
    }

    public function test_retrieving_nullable_typed_property()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('quantity', Type::int(), true, false, []),
            $classDefinition->getProperty('quantity')
        );
    }

    public function test_override_typed_property_with_annotation_type()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(Product::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('owners', Type::create("array<Test\Ecotone\Messaging\Fixture\Conversion\Admin>"), false, false, []),
            $classDefinition->getProperty('owners')
        );
    }

    public function test_ignoring_docblock_when_is_incorrect()
    {
        $classDefinition = ClassDefinition::createFor(Type::create(InCorrectArrayDocblock::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate('incorrectProperty', Type::array(), false, false, []),
            $classDefinition->getProperty('incorrectProperty')
        );
    }
}
