<?php

namespace Messaging\Config;

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
 * Class GatewayProxyBuilderTest
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilderTest extends MessagingTest
{
    public function test_creating_gateway_for_send_only_interface()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder =  GatewayProxyBuilder::create(ServiceInterfaceSendOnly::class, 'sendMail', $requestChannel);

        $gatewayProxy = $gatewayProxyBuilder->build();
        $gatewayProxy->execute(['test']);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder =  GatewayProxyBuilder::create(ServiceInterfaceSendOnly::class, 'sendMail', $requestChannel);

        $gatewayProxyBuilder->withReplyChannel(new QueueChannel());

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_creating_gateway_for_receive_only()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannel = new QueueChannel();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);

        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->execute([])
        );
    }

    public function test_throwing_exception_when_no_reply_channel_defined_for_receive_only()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_throwing_exception_if_return_type_is_not_defined()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceWithUnknownReturnType::class, 'sendMail', $requestChannel);

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_time_out_without_response()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannel = new QueueChannel();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);
        $gatewayProxyBuilder->withMillisecondTimeout(1);

        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->execute([])
        );
    }
}