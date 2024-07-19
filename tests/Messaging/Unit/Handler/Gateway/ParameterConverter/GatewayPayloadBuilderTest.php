<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway\ParameterConverter;

use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceWithMixed;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class GatewayPayloadBuilderTest extends TestCase
{
    public function test_resolving_class_type()
    {
        $messaging = ComponentTestBuilder::create()
            ->withGateway(
                GatewayProxyBuilder::create(
                    ServiceWithMixed::class,
                    ServiceWithMixed::class,
                    'send',
                    $inputChannel = 'inputChannel'
                )
                    ->withParameterConverters([
                        GatewayPayloadBuilder::create('value'),
                    ])
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withMessage')
                    ->withInputChannelName($inputChannel)
            )
            ->build();

        $message = $messaging->getGateway(ServiceWithMixed::class)
            ->send($payload = new stdClass());

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_resolving_class_type_by_checking_directly_sent_message()
    {
        $messaging = ComponentTestBuilder::create()
            ->withGateway(
                GatewayProxyBuilder::create(
                    ServiceWithMixed::class,
                    ServiceWithMixed::class,
                    'sendWithoutReturnValue',
                    $inputChannel = 'inputChannel'
                )
                    ->withParameterConverters([
                        GatewayPayloadBuilder::create('value'),
                    ])
            )
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($inputChannel))
            ->build();

        $messaging->getGateway(ServiceWithMixed::class)
            ->sendWithoutReturnValue($payload = new stdClass());

        $message = $messaging->receiveMessageFrom($inputChannel);
        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $message->getHeaders()->getContentType()
        );
    }
}
