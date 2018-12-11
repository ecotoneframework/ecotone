<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Gateway;

use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class GatewayHeaderExpressionBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderExpressionBuilderTest extends MessagingTest
{

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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