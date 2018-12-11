<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;
use Test\SimplyCodedSoftware\Messaging\Builder\Handler\InterfaceParameterTestCaseBuilder;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ReferenceBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_reference_converter()
    {
        $referenceName = "refName";
        $value = new \stdClass();
        $converter = ReferenceBuilder::create("paramName", $referenceName)
            ->build(InMemoryReferenceSearchService::createWith([
                $referenceName => $value
            ]));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock(\stdClass::class, "")),
                MessageBuilder::withPayload("paramName")->build()
            )
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_with_dynamic_reference_resolution()
    {
        $value = new \stdClass();
        $converter = ReferenceBuilder::createWithDynamicResolve("param")
            ->build(InMemoryReferenceSearchService::createWith([
                "\\" . \stdClass::class => $value
            ]));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock(\stdClass::class, "")),
                MessageBuilder::withPayload("paramName")->build()
            )
        );
    }
}