<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class GatewayHeaderExpressionBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class GatewayPayloadExpressionBuilderTest extends MessagingTest
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_evaluating_gateway_parameter()
    {
        $converter = ComponentTestBuilder::create()
            ->withReference('calculatingService', CalculatingService::create(1))
            ->build(GatewayPayloadExpressionBuilder::create('test', "reference('calculatingService').sum(value)"));

        $this->assertEquals(
            MessageBuilder::withPayload('some')
                ->setPayload(2),
            $converter->convertToMessage(
                MethodArgument::createWith(InterfaceParameter::createNullable('test', TypeDescriptor::create('string')), 1),
                MessageBuilder::withPayload('some')
            )
        );
    }
}
