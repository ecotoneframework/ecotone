<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceWithMixed;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class GatewayHeaderExpressionBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class GatewayPayloadExpressionBuilderTest extends MessagingTestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_evaluating_gateway_parameter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference('calculatingService', CalculatingService::create(1))
            ->withGateway(
                GatewayProxyBuilder::create(
                    ServiceWithMixed::class,
                    ServiceWithMixed::class,
                    'send',
                    $inputChannel = 'inputChannel'
                )
                    ->withParameterConverters([
                        GatewayPayloadExpressionBuilder::create('value', "reference('calculatingService').sum(value)"),
                    ])
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withMessage')
                    ->withInputChannelName($inputChannel)
            )
            ->build();

        $this->assertEquals(
            2,
            $messaging->getGateway(ServiceWithMixed::class)->send(1)->getPayload()
        );
    }
}
