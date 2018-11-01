<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use Builder\Handler\InterfaceParameterTestCaseBuilder;
use Fixture\Service\CalculatingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ExpressionBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExpressionBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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