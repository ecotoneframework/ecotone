<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Annotation\RequiredReferenceName;
use SimplyCodedSoftware\Messaging\Handler\ClassDefinition;
use SimplyCodedSoftware\Messaging\Handler\ClassPropertyDefinition;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Property\ExtendedOrderPropertyExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Property\OrderPropertyExample;

/**
 * Class ClassDefinitionTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler
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
                new RequiredReferenceName()
            ]),
            $classDefinition->getProperty("reference")
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
}