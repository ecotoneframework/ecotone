<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use Test\SimplyCodedSoftware\Messaging\Builder\Handler\InterfaceParameterTestCaseBuilder;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class StaticBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_static_value()
    {
        $value = new \stdClass();
        $converter = ValueBuilder::create("x", $value);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string",null)),
                MessageBuilder::withPayload("a")->build()
            )
        );
    }
}