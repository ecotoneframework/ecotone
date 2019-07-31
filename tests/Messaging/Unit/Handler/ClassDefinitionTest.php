<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ClassPropertyDefinition;
use Ecotone\Messaging\Handler\TypeDescriptor;
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
            ClassPropertyDefinition::createProtected("reference", TypeDescriptor::createStringType(), true, true, [
                new PropertyAnnotationExample()
            ]),
            $classDefinition->getProperty("reference")
        );
    }

    public function test_retrieving_property_with_annotation()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            [
                ClassPropertyDefinition::createProtected("reference", TypeDescriptor::createStringType(), true, true, [
                    new PropertyAnnotationExample()
                ])
            ],
            $classDefinition->getPropertiesWithAnnotation(TypeDescriptor::create(PropertyAnnotationExample::class))
        );
    }

    public function test_retrieving_public_property()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPublic("extendedName", TypeDescriptor::createUnknownType(), true, false, []),
            $classDefinition->getProperty("extendedName")
        );
    }

    public function test_retrieving_private_property_from_parent_class()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(ExtendedOrderPropertyExample::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("someClass", TypeDescriptor::create(\stdClass::class), true, false, []),
            $classDefinition->getProperty("someClass")
        );
    }

    public function test_retrieving_private_from_trait()
    {
        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create(OrderWithTraits::class));

        $this->assertEquals(
            ClassPropertyDefinition::createPrivate("property", TypeDescriptor::create(ExtraObject::class), true, false, []),
            $classDefinition->getProperty("property")
        );
    }
}