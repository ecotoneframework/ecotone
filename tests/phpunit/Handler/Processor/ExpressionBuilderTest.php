<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use Builder\Handler\InterfaceParameterTestCaseBuilder;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
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
                InterfaceParameter::create("x", "string", true, ""),
                MessageBuilder::withPayload($payload)->build()
            )
        );
    }
}