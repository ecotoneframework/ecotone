<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use Test\SimplyCodedSoftware\Messaging\Builder\Handler\InterfaceParameterTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ExpressionBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExpressionBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_payload_expression()
    {
        $converter = ExpressionBuilder::create("x", "payload ~ 1");
        $converter = $converter->build(InMemoryReferenceSearchService::createWith([
            ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
        ]));

        $payload = "rabbit";
        $this->assertEquals(
            $payload . "1",
            $converter->getArgumentFrom(
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string",  "")),
                MessageBuilder::withPayload($payload)->build()
            )
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_using_reference_service_in_expression()
    {
        $converter = ExpressionBuilder::create("x", "reference('calculatingService').sum(payload)");

        $converter = $converter->build(InMemoryReferenceSearchService::createWith([
            ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create(),
            "calculatingService" => CalculatingService::create(1)
        ]));

        $this->assertEquals(
            2,
            $converter->getArgumentFrom(
                InterfaceParameter::createNullable("x", TypeDescriptor::create("string")),
                MessageBuilder::withPayload(1)->build()
            )
        );
    }
}