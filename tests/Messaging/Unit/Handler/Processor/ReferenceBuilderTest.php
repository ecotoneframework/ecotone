<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;

/**
 * Class ReferenceBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
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
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock(\stdClass::class, "")),
                MessageBuilder::withPayload("paramName")->build(),
                []
            )
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_dynamic_reference_resolution()
    {
        $value = new \stdClass();
        $converter = ReferenceBuilder::createWithDynamicResolve("param")
            ->build(InMemoryReferenceSearchService::createWith([
                \stdClass::class => $value
            ]));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock(\stdClass::class, "")),
                MessageBuilder::withPayload("paramName")->build(),
                []
            )
        );
    }
}