<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use Fixture\Handler\ExceptionMessageHandler;
use Fixture\Handler\Gateway\DumbSendAndReceiveService;
use Fixture\Handler\NoReturnMessageHandler;
use Fixture\Handler\ReplyViaHeadersMessageHandler;
use Fixture\Handler\StatefulHandler;
use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnlyWithNull;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendAndReceive;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use Fixture\Service\ServiceInterface\ServiceInterfaceWithFutureReceive;
use Fixture\Service\ServiceInterface\ServiceInterfaceWithUnknownReturnType;
use Fixture\Service\ServiceInterface\ServiceReceivingMessageAndReturningMessage;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToStaticHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\ErrorMessage;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class GatewayProxyBuilderTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilderTest extends MessagingTest
{
    public function test_creating_gateway_for_send_only_interface()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );
        $gatewayProxy->sendMail('test');

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "req-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        $gatewayProxyBuilder->withReplyChannel("replyChannel");

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel),
                    NamedMessageChannel::create("replyChannel", QueueChannel::create())
                ]
            )
        );
    }

    public function test_creating_gateway_for_receive_only()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload          = 'replyData';
        $replyChannelName = "reply-channel";
        $replyMessage     = MessageBuilder::withPayload($payload)->build();
        $replyChannel     = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );
        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
        );
    }

    public function test_time_out_without_response()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload          = 'replyData';
        $replyChannelName = 'reply-channel';
        $replyChannel     = QueueChannel::create();
        $replyMessage     = MessageBuilder::withPayload($payload)->build();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        $gatewayProxyBuilder->withMillisecondTimeout(1);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );
        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
        );
    }

    public function test_executing_with_method_argument_converters()
    {
        $messageHandler     = StatefulHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $personId            = '123';
        $content             = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withParameterToMessageConverters(
            [
                ParameterToHeaderConverterBuilder::create('personId', 'personId'),
                ParameterToPayloadConverterBuilder::create('content')
            ]
        );

        /** @var ServiceInterfaceSendOnlyWithTwoArguments $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );
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
        $messageHandler     = StatefulHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $personId            = '123';
        $personName          = 'Johny';
        $content             = 'some bla content';
        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withParameterToMessageConverters(
            [
                ParameterToStaticHeaderConverterBuilder::create('personId', $personId),
                ParameterToPayloadConverterBuilder::create('content'),
                ParameterToStaticHeaderConverterBuilder::create('personName', $personName)
            ]
        );

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );
        $gatewayProxy->sendMail($content);

        $message = $messageHandler->message();
        $this->assertEquals($personId, $message->getHeaders()->get("personId"));
        $this->assertEquals($personName, $message->getHeaders()->get("personName"));
        $this->assertEquals($content, $message->getPayload());
    }

    public function test_executing_with_multiple_message_converters_for_same_parameter()
    {
        $messageHandler     = StatefulHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $content             = "testContent";
        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withParameterToMessageConverters(
            [
                ParameterToHeaderConverterBuilder::create('content', "test1"),
                ParameterToHeaderConverterBuilder::create('content', "test2")
            ]
        );

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );
        $gatewayProxy->sendMail($content);

        $message = $messageHandler->message();
        $this->assertEquals($content, $message->getHeaders()->get("test1"));
        $this->assertEquals($content, $message->getHeaders()->get("test2"));
    }

    public function test_resolving_response_in_future()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload          = 'replyData';
        $replyMessage     = MessageBuilder::withPayload($payload)->build();
        $replyChannelName = "reply-channel";
        $replyChannel     = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );
        $this->assertEquals(
            $payload,
            $gatewayProxy->someLongRunningWork()->resolve()
        );
    }

    public function test_throwing_exception_when_received_error_message()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $errorMessage     = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannelName = 'replyChannel';
        $replyChannel     = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->getById(1);
    }

    public function test_throwing_exception_when_received_error_message_for_future_reply_sender()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $errorMessage     = ErrorMessage::create(new \Exception("error occurred"), MessageHeaders::createEmpty());
        $replyChannelName = 'reply-channel';
        $replyChannel     = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );

        $this->expectException(MessageHandlingException::class);

        $gatewayProxy->someLongRunningWork()->resolve();
    }

    public function test_returning_null_when_no_reply_received_for_nullable_interface()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannelName = 'reply-channel';
        $replyChannel     = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );

        $this->assertNull(
            $gatewayProxy->sendMail()
        );
    }

    public function test_throwing_exception_when_reply_is_null_but_interface_expect_value()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $replyChannelName = 'reply-channel';
        $replyChannel     = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withMillisecondTimeout(1);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        $this->expectException(InvalidArgumentException::class);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel
                ]
            )
        );

        $gatewayProxy->sendMail();
    }

    public function test_creating_gateway_with_custom_reply_receiver()
    {
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe(ReplyViaHeadersMessageHandler::create("reply"));
        $reply = MessageBuilder::withPayload("extended reply")->build();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName)
            ->withCustomSendAndReceiveService(DumbSendAndReceiveService::create()->setMessageToReceive($reply));

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );

        $this->assertEquals(
            "extended reply",
            $gatewayProxy->sendMail()
        );
    }

    public function test_exception_containing_original_message()
    {
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe(ExceptionMessageHandler::create());

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );

        $this->expectExceptionObject(MessageHandlingException::fromOtherException(new \InvalidArgumentException("test"), MessageBuilder::withPayload("empty")->build()));

        $gatewayProxy->sendMail();
    }

    public function test_propagating_error_to_error_channel()
    {
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe(ExceptionMessageHandler::create());

        $errorChannel        = QueueChannel::create();
        $errorChannelName    = "errorChannel";
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $errorChannelName => $errorChannel
                ]
            )
        );

        $gatewayProxy->sendMail();

        $this->assertInstanceOf(
            ErrorMessage::class,
            $errorChannel->receive()
        );
    }

    public function test_requesting_with_original_message_and_returning_new_message()
    {
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $replyData          = MessageBuilder::withPayload("newMessage")->build();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($messageHandler);

        /** @var ServiceReceivingMessageAndReturningMessage $gateway */
        $gateway = GatewayProxyBuilder::create('ref-name', ServiceReceivingMessageAndReturningMessage::class, 'execute', $requestChannelName)
                        ->build(
                            InMemoryReferenceSearchService::createEmpty(),
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $requestChannelName => $requestChannel
                            ])
                        );

        $replyMessage = $gateway->execute(MessageBuilder::withPayload("some")->setHeader("token", "123")->build());

        $this->assertEquals(
            "some",
            $messageHandler->getReceivedMessage()->getPayload()
        );
        $this->assertEquals(
            "123",
            $messageHandler->getReceivedMessage()->getHeaders()->get("token")
        );

        $this->assertEquals(
            $replyData,
            $replyMessage
        );
    }
}
