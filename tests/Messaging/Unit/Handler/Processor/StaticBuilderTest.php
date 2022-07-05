<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Tests\Messaging\Fixture\Service\CallableService;

/**
 * Class StaticBuilderTest
 * @package Ecotone\Tests\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_static_value()
    {
        $value = new \stdClass();
        $converter = ValueBuilder::create("x", $value);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string",null)),
                MessageBuilder::withPayload("a")->build(),
                []
            )
        );
    }
}