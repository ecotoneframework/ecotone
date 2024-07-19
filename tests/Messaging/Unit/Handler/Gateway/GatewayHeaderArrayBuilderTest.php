<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceWithMixed;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class GatewayHeaderArrayBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Gateway
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class GatewayHeaderArrayBuilderTest extends MessagingTest
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
                        GatewayHeadersBuilder::create('value'),
                    ])
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withMessage')
                    ->withInputChannelName($inputChannel)
            )
            ->build();

        $message = $messaging->getGateway(ServiceWithMixed::class)->send([
            'token' => 'some',
            'type' => 'someType',
        ]);
        $this->assertEquals(
            'some',
            $message->getHeaders()->get('token')
        );
        $this->assertEquals(
            'someType',
            $message->getHeaders()->get('type')
        );
    }

    public function test_throwing_exception_if_passed_argument_is_not_array()
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
                        GatewayHeadersBuilder::create('value'),
                    ])
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withMessage')
                    ->withInputChannelName($inputChannel)
            )
            ->build();

        $this->expectException(InvalidArgumentException::class);

        $messaging->getGateway(ServiceWithMixed::class)->send(123);
    }
}
