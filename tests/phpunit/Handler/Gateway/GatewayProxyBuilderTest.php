<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Gateway;

use Fixture\Handler\NoReturnMessageHandler;
use Fixture\Handler\StatefulHandler;
use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnlyWithNull;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendAndReceive;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use Fixture\Service\ServiceInterface\ServiceInterfaceWithFutureReceive;
use Fixture\Service\ServiceInterface\ServiceInterfaceWithUnknownReturnType;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\HeaderMessageArgumentConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\PayloadMethodArgumentMessageParameter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\StaticHeaderMessageArgumentConverter;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class GatewayProxyBuilderTest
 * @package SimplyCodedSoftware\Messaging\Config
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

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $gatewayProxy->sendMail('test');

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder =  GatewayProxyBuilder::create(ServiceInterfaceSendOnly::class, 'sendMail', $requestChannel);

        $gatewayProxyBuilder->withReplyChannel(QueueChannel::create());

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_creating_gateway_for_receive_only()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannel = QueueChannel::create();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
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

    public function test_throwing_exception_if_service_has_no_return_type()
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
        $replyChannel = QueueChannel::create();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);
        $gatewayProxyBuilder->withMillisecondTimeout(1);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
        );
    }

    public function test_executing_with_method_argument_converters()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = StatefulHandler::create();
        $requestChannel->subscribe($messageHandler);

        $personId = '123';
        $content = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withMethodArgumentConverters([
            HeaderMessageArgumentConverter::create('personId', 'personId'),
            PayloadMethodArgumentMessageParameter::create('content')
        ]);

        /** @var ServiceInterfaceSendOnlyWithTwoArguments $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $gatewayProxy->sendMail($personId, $content);

        $this->assertMessages(
            MessageBuilder::withPayload($content)
                ->setHeader('personId', $personId)
                ->setHeader("errorChannel", "errorChannel")
                ->build(),
            $messageHandler->message()
        );
    }

    public function test_executing_with_multiple_converters_for_single_parameter_interface()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = StatefulHandler::create();
        $requestChannel->subscribe($messageHandler);

        $personId = '123';
        $personName = 'Johny';
        $content = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceSendOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withMethodArgumentConverters([
            StaticHeaderMessageArgumentConverter::create('personId', $personId),
            PayloadMethodArgumentMessageParameter::create('content'),
            StaticHeaderMessageArgumentConverter::create('personName', $personName)
        ]);

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $gatewayProxy->sendMail($content);

        $this->assertMessages(
            MessageBuilder::withPayload($content)
                ->setHeader('personId', $personId)
                ->setHeader('personName', $personName)
                ->setHeader(MessageHeaders::ERROR_CHANNEL, "errorChannel")
                ->build(),
            $messageHandler->message()
        );
    }

    public function test_resolving_response_in_future()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannel = QueueChannel::create();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);

        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->someLongRunningWork()->resolve()
        );
    }

    public function test_throwing_exception_when_received_error_message()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannel = QueueChannel::create();
        $errorMessage = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceSendAndReceive::class, 'getById', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);
        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->getById(1);
    }

    public function test_throwing_exception_when_received_error_message_for_future_reply_sender()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannel = QueueChannel::create();
        $errorMessage = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannel);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);
        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->someLongRunningWork()->resolve();
    }

    public function test_returning_null_when_no_reply_received_for_nullable_interface()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->assertNull(
            $gatewayProxy->sendMail()
        );
    }

    public function test_throwing_exception_when_reply_is_null_but_interface_expect_value()
    {
        $requestChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create(ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannel);
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannel);

        $this->expectException(InvalidArgumentException::class);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $gatewayProxy->sendMail();
    }
}