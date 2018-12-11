<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use Test\SimplyCodedSoftware\Messaging\Builder\Handler\InterfaceParameterTestCaseBuilder;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class PayloadBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadBuilderTest extends MessagingTest
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_payload_converter()
    {
        $converter = PayloadBuilder::create("some");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $payload = "rabbit";
        $this->assertEquals(
              $payload,
              $converter->getArgumentFrom(
                  InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string", "")),
                  MessageBuilder::withPayload($payload)->build()
              )
        );
    }
}