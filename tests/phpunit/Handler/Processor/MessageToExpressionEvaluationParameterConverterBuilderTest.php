<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use Fixture\Dto\WithCustomer\Customer;
use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToExpressionEvaluationParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MessageToExpressionEvaluationParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToExpressionEvaluationParameterConverterBuilderTest extends TestCase
{
    public function test_evaluating_header()
    {
        $messageHandlerBuilderWithParameterConverters = DumbMessageHandlerBuilder::createSimple();
        $expressionEvaluationParameter                = MessageToExpressionEvaluationParameterConverterBuilder::createWith(
            "name", "payload.getUsername()", $messageHandlerBuilderWithParameterConverters
        )->build(
            InMemoryReferenceSearchService::createWith(
                [
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                ]
            )
        );

        $username = "johny";
        $this->assertEquals(
            $username,
            $expressionEvaluationParameter->getArgumentFrom(MessageBuilder::withPayload(Customer::createWithUsernameOnly($username))->build())
        );
        $this->assertEquals(
            [NoReturnMessageHandler::class, ExpressionEvaluationService::REFERENCE],
            $messageHandlerBuilderWithParameterConverters->getRequiredReferenceNames()
        );
    }
}