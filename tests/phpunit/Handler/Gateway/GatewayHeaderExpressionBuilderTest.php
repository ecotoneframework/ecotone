<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use Fixture\Service\CalculatingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class GatewayHeaderExpressionBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderExpressionBuilderTest extends MessagingTest
{

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_evaluating_gateway_parameter()
    {
        $converter = GatewayHeaderExpressionBuilder::create("test", "token", "reference('calculatingService').sum(value)")
                        ->build(InMemoryReferenceSearchService::createWith([
                            ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create(),
                            "calculatingService" => CalculatingService::create(1)
                        ]));

        $this->assertEquals(
            MessageBuilder::withPayload("some")
                ->setHeader("token", 2),
            $converter->convertToMessage(
                MethodArgument::createWith(InterfaceParameter::createNullable("test", TypeDescriptor::create("string")), 1),
                MessageBuilder::withPayload("some")
            )
        );
    }
}