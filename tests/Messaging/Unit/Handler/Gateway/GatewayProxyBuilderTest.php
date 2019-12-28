<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Annotation\Gateway\GatewayHeader;
use Ecotone\Messaging\Annotation\Gateway\GatewayHeaderArray;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyConfiguration;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodArgumentReplacementException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\InvalidMessageHeaderException;
use Ecotone\Messaging\Transaction\Null\NullTransaction;
use Ecotone\Messaging\Transaction\Null\NullTransactionFactory;
use Ecotone\Messaging\Transaction\Transactional;
use Ecotone\Messaging\Transaction\TransactionInterceptor;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\DumbSendAndReceiveService;
use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayClassAndMethodExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayClassExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayMethodExample;
use Test\Ecotone\Messaging\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\StatefulHandler;
use Test\Ecotone\Messaging\Fixture\MessageConverter\FakeMessageConverter;
use Test\Ecotone\Messaging\Fixture\MessageConverter\FakeMessageConverterGatewayExample;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\MessageServiceExample;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingNoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceCalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnlyWithNull;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendAndReceive;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceWithFutureReceive;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceWithUnknownReturnType;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceReceivingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Fixture\Transaction\FakeTransaction;
use Test\Ecotone\Messaging\Fixture\Transaction\FakeTransactionFactory;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Config\NamedMessageChannel;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class GatewayProxyBuilderTest
 * @package Ecotone\Messaging\Config
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

        $this->assertEquals(
            $personId,
            $messageHandler->message()->getHeaders()->get('personId')
        );
        $this->assertEquals(
            $content, $messageHandler->message()->getPayload()
        );
    }

    public function test_throwing_exception_if_two_payload_converters_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', "requestChannel");
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayPayloadBuilder::create('content'),
                GatewayPayloadBuilder::create('personId')
            ]
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

    public function test_converters_execution_according_to_order_in_list()
    {
        $messageHandler     = StatefulHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create("ref-name", ServiceInterfaceSendOnly::class, 'sendMailWithMetadata', $requestChannelName);
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderValueBuilder::create('personId', 1),
                GatewayHeadersBuilder::create('metadata'),
                GatewayHeaderBuilder::create('content', 'personId')
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
        $gatewayProxy->sendMailWithMetadata(3, ["personId" => 2]);

        $message = $messageHandler->message();
        $this->assertEquals(3, $message->getHeaders()->get("personId"));
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
        $this->assertNull($requestChannel->receive());
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

        $errorMessage     = ErrorMessage::create(MessageHandlingException::create("error occurred"));
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

        $errorMessage     = ErrorMessage::create(MessageHandlingException::create("error occurred"));
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
        $this->assertNull($errorChannel->receive());
    }

    public function test_propagating_error_to_error_channel_when_exception_happen_during_receiving_reply()
    {
        $replyChannelName = "replyChannel";
        $replyChannel        = $this->createMock(PollableChannel::class);
        $exception = new \RuntimeException("some error");
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

        /** @var ErrorMessage $errorMessage */
        $errorMessage = $errorChannel->receive();
        $this->assertNotNull($errorMessage, "Error message was not sent to error channel");
        $this->assertEquals($exception->getMessage(), $errorMessage->getPayload()->getMessage());
    }

    public function test_propagating_error_to_error_channel_when_error_message_received_from_reply_channel()
    {
        $replyChannelName = "replyChannel";
        $exception = new \RuntimeException("Error happened");
        $replyChannel = QueueChannel::create();
        $exception = MessageHandlingException::createWithFailedMessage($exception, MessageBuilder::withPayload("some")->build());
        $errorMessage = ErrorMessage::create($exception);
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

        $gatewayProxy->getById(123);

        /** @var ErrorMessage $errorMessage */
        $errorMessage = $errorChannel->receive();
        $this->assertNotNull($errorMessage, "Error message never arrived on error channel");
        $this->assertEquals($exception, $errorMessage->getPayload());
    }

    public function test_throwing_root_cause_exception_when_no_error_channel_defined()
    {
        $replyChannelName = "replyChannel";
        $replyChannel = QueueChannel::create();
        $exception = MessageHandlingException::fromOtherException(new \RuntimeException("Error happened"), MessageBuilder::withPayload("some")->build());
        $errorMessage = ErrorMessage::create($exception);
        $replyChannel->send($errorMessage);

        $requestChannelName = "requestChannel";
        $requestChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName);

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

        $this->expectException(\RuntimeException::class);

        $gatewayProxy->getById(123);
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
            $replyData->getPayload(),
            $replyMessage->getPayload()
        );
    }

    public function test_calling_interface_with_around_interceptor_from_endpoint_annotation()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
            ->withEndpointAnnotations([
                Transactional::createWith(["transactionFactory"])
            ])
            ->addAroundInterceptor(
                AroundInterceptorReference::create("transactionInterceptor","transactionInterceptor", "transactional", 1, "")
            );

        $this->assertEquals([ProxyFactory::REFERENCE_NAME, "transactionInterceptor"], $gatewayProxyBuilder->getRequiredReferences());

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory" => $transactionFactoryOne,
                "transactionInterceptor" => $transactionInterceptor
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_throwing_exception_when_replacing_argument_in_around_interceptor()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject("",CalculatingServiceInterceptorExample::create(1), "sum", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxy->calculate(5);
    }


    public function test_calling_interface_with_around_interceptor_from_method_annotation()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayMethodExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject("transactionInterceptor",$transactionInterceptor, "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory" => $transactionFactoryOne
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_from_class_annotation()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject("transactionInterceptor",$transactionInterceptor, "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory" => $transactionFactoryOne
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_and_choosing_method_annotation_over_class()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassAndMethodExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject("transactionInterceptor",$transactionInterceptor, "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory2" => $transactionFactoryOne
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_and_choosing_endpoint_annotation_over_method()
    {
        $messageHandler     = NoReturnMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassAndMethodExample::class, 'invoke', $requestChannelName)
            ->withEndpointAnnotations([Transactional::createWith(["transactionFactory0"])])
            ->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject("transactionInterceptor",$transactionInterceptor, "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory0" => $transactionFactoryOne
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel)
                ]
            )
        );

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_before_and_after_interceptors()
    {
        $messageHandler     = ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                                    ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
            ->addBeforeInterceptor(
                MethodInterceptor::create(
                    "interceptor0",
                    InterfaceToCall::create(CalculatingService::class, "multiply"),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply"),
                    0,
                    ""
                )
            )
            ->addBeforeInterceptor(
                MethodInterceptor::create(
                    "interceptor1",
                    InterfaceToCall::create(CalculatingService::class, "sum"),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "sum"),
                    1,
                    ""
                )
            )
            ->addAfterInterceptor(
                MethodInterceptor::create(
                    "interceptor2",
                    InterfaceToCall::create(CalculatingService::class, "result"),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "result"),
                    1,
                    ""
                )
            )
            ->addAfterInterceptor(
                MethodInterceptor::create(
                    "interceptor3",
                    InterfaceToCall::create(CalculatingService::class, "multiply"),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"),
                    0,
                    ""
                )
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelName => $requestChannel
                ]
            )
        );

        $this->assertEquals(20, $gatewayProxy->calculate(2));
    }


    public function test_calling_around_interceptors_before_sending_to_error_channel()
    {
        $messageHandler     = ExceptionMessageHandler::create();
        $requestChannelName = "request-channel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
            ->withErrorChannel("some")
            ->withEndpointAnnotations([
                Transactional::createWith(["transactionFactory"])
            ])
            ->addAroundInterceptor(
                AroundInterceptorReference::create("transactionInterceptor","transactionInterceptor", "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory" => $transactionFactoryOne,
                "transactionInterceptor" => $transactionInterceptor
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, $requestChannel),
                    NamedMessageChannel::create("some", QueueChannel::create())
                ]
            )
        );

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isRolledBack());
    }

    public function test_calling_interceptors_before_sending_to_error_channel_when_receive_throws_error()
    {
        $requestChannelName = "request-channel";
        $replyChannel        = $this->createMock(PollableChannel::class);
        $exception = new \RuntimeException();
        $replyChannel
            ->method("receive")
            ->willThrowException($exception);


        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName)
            ->withReplyChannel("replyChannel")
            ->withErrorChannel("some")
            ->withEndpointAnnotations([
                Transactional::createWith(["transactionFactory"])
            ])
            ->addAroundInterceptor(
                AroundInterceptorReference::create("transactionInterceptor","transactionInterceptor", "transactional", 1, "")
            );

        $gatewayProxy = $gatewayProxyBuilder->build(
            InMemoryReferenceSearchService::createWith([
                "transactionFactory" => $transactionFactoryOne,
                "transactionInterceptor" => $transactionInterceptor
            ]),
            InMemoryChannelResolver::create(
                [
                    NamedMessageChannel::create($requestChannelName, QueueChannel::create()),
                    NamedMessageChannel::create("some", QueueChannel::create()),
                    NamedMessageChannel::create("replyChannel", $replyChannel)
                ]
            )
        );

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isRolledBack());
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

    public function test_throwing_exception_if_creating_gateway_with_error_channel_and_interface_can_not_return_null()
    {
        $this->expectException(InvalidArgumentException::class);

        GatewayProxyBuilder::create("some", ServiceInterfaceReceiveOnly::class, 'sendMail', "requestChannel")
            ->withErrorChannel("errorChannel")
            ->build(
                InMemoryReferenceSearchService::createEmpty(),
                InMemoryChannelResolver::createFromAssociativeArray([
                    "requestChannel" => DirectChannel::create(),
                    "errorChannel" => QueueChannel::create()
                ])
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
