<?php

namespace Messaging\Handler;

use Fixture\Handler\NoReturnMessageHandler;
use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceWithUnknownReturnType;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
use Messaging\Handler\Gateway\GatewayFactory;
use Messaging\Handler\Gateway\GatewayReply;
use Messaging\MessagingTest;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class GatewayFactoryTest
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayFactoryTest extends MessagingTest
{
    public function test_creating_gateway_for_receive_only_interface()
    {
        $gatewayFactory = new GatewayFactory();
        $replyMessage = MessageBuilder::withPayload('reply')
                        ->build();
        $replyChannel = new QueueChannel();
        $replyChannel->send($replyMessage);
        $responseChannel = new QueueChannel();


        $gateway = $gatewayFactory->createFor(
            ServiceInterfaceReceiveOnly::class,
            'sendMail',
            $this->requestChannel(),
            0,
            new GatewayReply($replyChannel, $responseChannel)
        );

        $gateway->handle(MessageBuilder::withPayload('test')->build());

        $this->assertEquals(
            $replyMessage,
            $responseChannel->receive()
        );
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $gatewayFactory = new \Messaging\Handler\Gateway\GatewayFactory();

        $this->expectException(InvalidArgumentException::class);

        $gatewayFactory->createFor(
            ServiceInterfaceSendOnly::class,
            'sendMail',
            $this->requestChannel(),
            0,
            new GatewayReply(new QueueChannel(), new QueueChannel())
        );
    }

    public function test_throwing_exception_if_return_type_is_not_defined()
    {
        $gatewayFactory = new \Messaging\Handler\Gateway\GatewayFactory();

        $this->expectException(InvalidArgumentException::class);

        $gatewayFactory->createFor(
            ServiceInterfaceWithUnknownReturnType::class,
            'sendMail',
            $this->requestChannel(),
            0,
            new GatewayReply(new QueueChannel(), new QueueChannel())
        );
    }

    public function test_creating_gateway_for_send_only_interface()
    {
        $gatewayFactory = new GatewayFactory();

        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gateway = $gatewayFactory->createFor(
            ServiceInterfaceSendOnly::class,
            'sendMail',
            $requestChannel,
            0,
            null
        );

        $gateway->handle(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandler->wasCalled(), "Message handler for gateway send only was not called");
    }

    public function test_time_out_without_response()
    {
        $gatewayFactory = new \Messaging\Handler\Gateway\GatewayFactory();
        $replyChannel = new QueueChannel();
        $responseChannel = new QueueChannel();


        $gateway = $gatewayFactory->createFor(
            ServiceInterfaceReceiveOnly::class,
            'sendMail',
            $this->requestChannel(),
            1,
            new \Messaging\Handler\Gateway\GatewayReply($replyChannel, $responseChannel)
        );

        $gateway->handle(MessageBuilder::withPayload('test')->build());

        $this->assertEquals(
            null,
            $responseChannel->receive()
        );
    }

    /**
     * @return DirectChannel
     */
    private function requestChannel(): DirectChannel
    {
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(NoReturnMessageHandler::create());
        return $requestChannel;
    }
}