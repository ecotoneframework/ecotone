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
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\HeaderMessageParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\PayloadMethodParameterToMessageMessageParameter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\StaticHeaderMessageParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
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
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder =  GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::create([
            NamedMessageChannel::create($requestChannelName, $requestChannel)
        ]));

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $gatewayProxy->sendMail('test');

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "req-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder =  GatewayProxyBuilder::create("ref-name",ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::create([
            NamedMessageChannel::create($requestChannelName, $requestChannel),
            NamedMessageChannel::create("replyChannel", QueueChannel::create())
        ]));

        $gatewayProxyBuilder->withReplyChannel("replyChannel");

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_creating_gateway_for_receive_only()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannelName = "reply-channel";
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name",ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel,
            $replyChannelName => $replyChannel
        ]));

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
        );
    }

    public function test_throwing_exception_when_no_reply_channel_defined_for_receive_only()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel
        ]));

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_throwing_exception_if_service_has_no_return_type()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceWithUnknownReturnType::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $requestChannelName => $requestChannel
        ]));

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build();
    }

    public function test_time_out_without_response()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $requestChannelName => $requestChannel,
                $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
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
        $messageHandler = StatefulHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $personId = '123';
        $content = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel
        ]));
        $gatewayProxyBuilder->withMethodArgumentConverters([
            HeaderMessageParameterToMessageConverter::create('personId', 'personId'),
            PayloadMethodParameterToMessageMessageParameter::create('content')
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
        $messageHandler = StatefulHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $personId = '123';
        $personName = 'Johny';
        $content = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel
        ]));
        $gatewayProxyBuilder->withMethodArgumentConverters([
            StaticHeaderMessageParameterToMessageConverter::create('personId', $personId),
            PayloadMethodParameterToMessageMessageParameter::create('content'),
            StaticHeaderMessageParameterToMessageConverter::create('personName', $personName)
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
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannelName = "reply-channel";
        $replyChannel = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel,
            $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();
        $this->assertEquals(
            $payload,
            $gatewayProxy->someLongRunningWork()->resolve()
        );
    }

    public function test_throwing_exception_when_received_error_message()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $errorMessage = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannelName = 'replyChannel';
        $replyChannel = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel,
            $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->getById(1);
    }

    public function test_throwing_exception_when_received_error_message_for_future_reply_sender()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $errorMessage = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel,
            $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->someLongRunningWork()->resolve();
    }

    public function test_returning_null_when_no_reply_received_for_nullable_interface()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
            $requestChannelName => $requestChannel,
            $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $this->assertNull(
            $gatewayProxy->sendMail()
        );
    }

    public function test_throwing_exception_when_reply_is_null_but_interface_expect_value()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $requestChannelName => $requestChannel,
                $replyChannelName => $replyChannel
        ]));
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        $this->expectException(InvalidArgumentException::class);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build();

        $gatewayProxy->sendMail();
    }
}
