<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Gateway;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Gateway\DumbSendAndReceiveService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\StatefulHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter\FakeMessageConverter;
use Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter\FakeMessageConverterGatewayExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\MessageServiceExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingNoArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnlyWithNull;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendAndReceive;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceWithFutureReceive;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceWithUnknownReturnType;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceReceivingMessageAndReturningMessage;
use Test\SimplyCodedSoftware\Messaging\Fixture\Transaction\FakeTransaction;
use Test\SimplyCodedSoftware\Messaging\Fixture\Transaction\FakeTransactionFactory;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class GatewayProxyBuilderTest
 * @package SimplyCodedSoftware\Messaging\Config
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

    public function test_calling_reply_queue_with_time_out()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload          = 'replyData';
        $replyMessage     = MessageBuilder::withPayload($payload)->build();
        $replyChannelName = 'reply-channel';
        $replyChannel     = $this->createMock(PollableChannel::class);
        $replyChannel
            ->method("receiveWithTimeout")
            ->with(1)
            ->willReturn($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        $gatewayProxyBuilder->withReplyMillisecondTimeout(1);

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
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderBuilder::create('personId', 'personId'),
                GatewayPayloadBuilder::create('content')
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
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderValueBuilder::create('personId', $personId),
                GatewayPayloadBuilder::create('content'),
                GatewayHeaderValueBuilder::create('personName', $personName)
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
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderBuilder::create('content', "test1"),
                GatewayHeaderBuilder::create('content', "test2")
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

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_gateway_expect_reply_and_request_channel_is_queue()
    {
        $requestChannelName = "requestChannel";
        $requestChannel = QueueChannel::create();
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_creating_with_queue_channel_when_gateway_does_not_expect_reply()
    {
        $requestChannelName = "requestChannel";
        $requestChannel = QueueChannel::create();
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        /** @var ServiceInterfaceSendOnly $gateway */
        $gateway = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );
        $gateway->sendMail('some');

        $this->assertEquals(
              $requestChannel->receive()->getPayload(),
              'some'
        );
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

        $this->expectException(\Exception::class);

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

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_gateway_in_gateway_messaging()
    {
        $methodName = 'execute';
        $interfaceName = ServiceReceivingMessageAndReturningMessage::class;
        $requestChannel     = DirectChannel::create();

        $internalChannel = DirectChannel::create();
        $internalChannel->subscribe(ReplyViaHeadersMessageHandler::createAdditionToPayload(4));
        /** @var ServiceReceivingMessageAndReturningMessage $internalGatewayProxy1 */
        $internalGatewayProxy1 = $this->createGateway($interfaceName, $methodName, $internalChannel);
        $internalChannel = DirectChannel::create();
        $internalChannel->subscribe(ReplyViaHeadersMessageHandler::createAdditionToPayload(2));
        /** @var ServiceReceivingMessageAndReturningMessage $internalGatewayProxy2 */
        $internalGatewayProxy2 = $this->createGateway($interfaceName, $methodName, $internalChannel);
        $requestChannel->subscribe(ReplyViaHeadersMessageHandler::createWithCallback(function(Message $message) use($internalGatewayProxy1, $internalGatewayProxy2) {
            $result = $internalGatewayProxy1->execute($message);
            $result = $internalGatewayProxy2->execute($result);

            return $result;
        }));

        /** @var ServiceReceivingMessageAndReturningMessage $gatewayProxy */
        $gatewayProxy = $this->createGateway($interfaceName, $methodName, $requestChannel);

        $replyChannel = QueueChannel::create();
        $errorChannel = DirectChannel::create();
        $message = $gatewayProxy->execute(
            MessageBuilder::withPayload(0)
                ->setReplyChannel($replyChannel)
                ->setErrorChannel($errorChannel)
                ->build()
        );
        $this->assertEquals(6, $message->getPayload());
        $this->assertEquals($replyChannel, $message->getHeaders()->getReplyChannel());
        $this->assertEquals($errorChannel, $message->getHeaders()->getErrorChannel());
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

    public function test_propagating_error_to_error_channel_when_exception_happen_during_receiving_reply()
    {
        $replyChannelName = "replyChannel";
        $replyChannel        = $this->createMock(PollableChannel::class);
        $exception = new \RuntimeException();
        $replyChannel
            ->method("receive")
            ->willThrowException($exception);

        $requestChannelName = "requestChannel";
        $requestChannel = QueueChannel::create();

        $errorChannelName    = "errorChannel";
        $errorChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel,
                    $errorChannelName => $errorChannel
                ]
            )
        );

        $gatewayProxy->getById(123);

        /** @var ErrorMessage $receivedMessage */
        $receivedMessage = $errorChannel->receive();
        $this->assertNull($receivedMessage->getFailedMessage());
        $this->assertEquals($exception, $receivedMessage->getException());
        $this->assertEquals(123, $receivedMessage->getOriginalMessage()->getPayload());
    }

    public function test_propagating_error_to_error_channel_when_error_message_received_from_reply_channel()
    {
        $replyChannelName = "replyChannel";
        $exception = new \RuntimeException();
        $replyChannel = QueueChannel::create();
        $errorMessage = ErrorMessage::createWithFailedMessage($exception, MessageBuilder::withPayload("some")->build());
        $replyChannel->send($errorMessage);

        $requestChannelName = "requestChannel";
        $requestChannel = QueueChannel::create();

        $errorChannelName    = "errorChannel";
        $errorChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel,
                    $replyChannelName => $replyChannel,
                    $errorChannelName => $errorChannel
                ]
            )
        );

        $this->assertNull($gatewayProxy->getById(123));

        /** @var ErrorMessage $receivedMessage */
        $receivedMessage = $errorChannel->receive();
        $this->assertEquals($errorMessage->getFailedMessage(), $receivedMessage->getFailedMessage());
        $this->assertEquals($errorMessage->getException(), $receivedMessage->getException());
        $this->assertEquals(123, $receivedMessage->getOriginalMessage()->getPayload());
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

        $this->assertMessages(
            $replyData,
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_calling_gateway_with_success_transactions()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);
        $transactionFactoryReferenceNames = ["trans1", "trans2"];

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
                                ->withTransactionFactories($transactionFactoryReferenceNames);

        $this->assertEquals($transactionFactoryReferenceNames, $gatewayProxyBuilder->getRequiredReferences());


        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $transactionFactoryOne = FakeTransactionFactory::create();
        $transactionFactoryTwo = FakeTransactionFactory::create();
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "trans1" => $transactionFactoryOne,
                "trans2" => $transactionFactoryTwo
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $this->assertNull($transactionFactoryOne->getCurrentTransaction());
        $this->assertNull($transactionFactoryTwo->getCurrentTransaction());

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionFactoryOne->getCurrentTransaction()->isCommitted());
        $this->assertTrue($transactionFactoryTwo->getCurrentTransaction()->isCommitted());
    }

    public function test_calling_gateway_with_failure_transactions()
    {
        $messageHandler     = ExceptionMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);
        $transactionFactoryReferenceNames = ["trans1"];

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
            ->withTransactionFactories($transactionFactoryReferenceNames);

        $this->assertEquals($transactionFactoryReferenceNames, $gatewayProxyBuilder->getRequiredReferences());

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $transactionFactoryOne = FakeTransactionFactory::create();
        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "trans1" => $transactionFactoryOne
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $this->assertNull($transactionFactoryOne->getCurrentTransaction());

        try {
            $gatewayProxy->sendMail('test');
        }catch (\InvalidArgumentException $e) {
            $this->assertTrue($transactionFactoryOne->getCurrentTransaction()->isRolledBack());
            return;
        }

        $this->assertTrue(false, "Transaction was not rolled back");
    }

    public function test_converting_to_string()
    {
        $requestChannelName = 'inputChannel';
        $referenceName = 'ref-name';

        $this->assertEquals(
            GatewayProxyBuilder::create($referenceName, ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName),
            sprintf("Gateway - %s:%s with reference name `%s` for request channel `%s`", ServiceInterfaceSendOnly::class, "sendMail", $referenceName, $requestChannelName)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_using_message_converter_for_transformation_according_to_interface()
    {
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $replyData          = MessageBuilder::withPayload("newMessage")->build();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($messageHandler);

        /** @var FakeMessageConverterGatewayExample $gateway */
        $gateway = GatewayProxyBuilder::create('ref-name', FakeMessageConverterGatewayExample::class, 'execute', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderBuilder::create("some", "some"),
                GatewayPayloadBuilder::create("amount")
            ])
            ->withMessageConverters([
                "converter"
            ])
            ->build(
                InMemoryReferenceSearchService::createWith([
                    "converter" => new FakeMessageConverter()
                ]),
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ])
            );

        $this->assertEquals(
            new \stdClass(),
            $gateway->execute([], 100)
        );
    }

    /**
     * @param $interfaceName
     * @param $methodName
     * @param $requestChannel
     * @param null|PollableChannel $replyChannel
     * @return object|\ProxyManager\Proxy\RemoteObjectInterface
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function createGateway($interfaceName, $methodName, $requestChannel, ?PollableChannel $replyChannel = null)
    {
        $gatewayProxyBuilder = GatewayProxyBuilder::create("some", $interfaceName, $methodName, "requestChannel");

        if ($replyChannel) {
            $gatewayProxyBuilder->withReplyChannel("replyChannel");
        }

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    "requestChannel" => $requestChannel,
                    "replyChannel" => $replyChannel ? $replyChannel : QueueChannel::create()
                ]
            )
        );
        return $gatewayProxy;
    }
}
