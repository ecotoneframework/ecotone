<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\BeforeSend\BeforeSendGateway;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Conversion\ArrayToJson\ArrayToJsonConverter;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\JsonToArray\JsonToArrayConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverter;
use Ecotone\Messaging\Conversion\UuidToString\UuidToStringConverter;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Messaging\Transaction\Null\NullTransaction;
use Ecotone\Messaging\Transaction\Null\NullTransactionFactory;
use Ecotone\Messaging\Transaction\Transactional;
use Ecotone\Messaging\Transaction\TransactionInterceptor;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use ProxyManager\Proxy\RemoteObjectInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleStdClassConverter;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExceptionalConverter;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Channel\PollingChannelThrowingException;
use Test\Ecotone\Messaging\Fixture\Handler\DataReturningService;
use Test\Ecotone\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MessageReturningGateway;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MixedReturningGateway;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\StringReturningGateway;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\UuidReturningGateway;
use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayClassAndMethodExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayClassExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorOnGatewayMethodExample;
use Test\Ecotone\Messaging\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\StatefulHandler;
use Test\Ecotone\Messaging\Fixture\MessageConverter\FakeMessageConverter;
use Test\Ecotone\Messaging\Fixture\MessageConverter\FakeMessageConverterGatewayExample;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceCalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnlyWithNull;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendAndReceive;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceWithFutureReceive;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceReceivingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class GatewayProxyBuilderTest
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class GatewayProxyBuilderTest extends MessagingTest
{
    public function test_creating_gateway_for_send_only_interface()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);
        $gatewayProxy->sendMail('test');

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_reply_channel_passed_for_send_only_interface()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'req-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        $gatewayProxyBuilder->withReplyChannel('replyChannel');

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel('replyChannel', QueueChannel::create())
            ->build($gatewayProxyBuilder);
    }

    public function test_creating_gateway_for_receive_only()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyChannelName = 'reply-channel';
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannel = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $this->assertEquals(
            $payload,
            $gatewayProxy->sendMail()
        );
    }

    public function test_calling_reply_queue_with_time_out()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannelName = 'reply-channel';
        $replyChannel = new class ($replyMessage) extends QueueChannel {
            public function __construct(private Message $replyMessage)
            {
                parent::__construct('');
            }
            public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
            {
                if ($timeoutInMilliseconds === 1) {
                    return $this->replyMessage;
                }
                throw InvalidArgumentException::create("Timeout should be 1, but got {$timeoutInMilliseconds}");
            }
        };

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        $gatewayProxyBuilder->withReplyMillisecondTimeout(1);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

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
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderBuilder::create('personId', 'personId'),
                GatewayPayloadBuilder::create('content'),
            ]
        );

        /** @var ServiceInterfaceSendOnlyWithTwoArguments $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->sendMail($personId, $content);

        $this->assertEquals(
            $personId,
            $messageHandler->message()->getHeaders()->get('personId')
        );
        $this->assertEquals(
            $content,
            $messageHandler->message()->getPayload()
        );
    }

    public function test_throwing_exception_if_two_payload_converters_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', 'requestChannel');
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayPayloadBuilder::create('content'),
                GatewayPayloadBuilder::create('personId'),
            ]
        );
    }

    public function test_converters_execution_according_to_order_in_list()
    {
        $messageHandler = StatefulHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMailWithMetadata', $requestChannelName);
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeadersBuilder::create('metadata'),
                GatewayHeaderBuilder::create('content', 'personId'),
            ]
        );

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);
        $gatewayProxy->sendMailWithMetadata(3, ['personId' => 2]);

        $message = $messageHandler->message();
        $this->assertEquals(3, $message->getHeaders()->get('personId'));
    }

    public function test_executing_with_multiple_message_converters_for_same_parameter()
    {
        $messageHandler = StatefulHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $content = 'testContent';
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withParameterConverters(
            [
                GatewayHeaderBuilder::create('content', 'test1'),
                GatewayHeaderBuilder::create('content', 'test2'),
            ]
        );

        /** @var ServiceInterfaceSendOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);
        $gatewayProxy->sendMail($content);

        $message = $messageHandler->message();
        $this->assertEquals($content, $message->getHeaders()->get('test1'));
        $this->assertEquals($content, $message->getHeaders()->get('test2'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_gateway_expect_reply_and_request_channel_is_queue()
    {
        $requestChannelName = 'requestChannel';
        $requestChannel = QueueChannel::create();
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnly::class, 'sendMail', $requestChannelName);

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->build($gatewayProxyBuilder);
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_creating_with_queue_channel_when_gateway_does_not_expect_reply()
    {
        $requestChannelName = 'requestChannel';
        $requestChannel = QueueChannel::create();
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName);

        /** @var ServiceInterfaceSendOnly $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);
        $gateway->sendMail('some');

        $this->assertEquals(
            $requestChannel->receive()->getPayload(),
            'some'
        );
        $this->assertNull($requestChannel->receive());
    }

    public function test_resolving_response_in_future()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $payload = 'replyData';
        $replyMessage = MessageBuilder::withPayload($payload)->build();
        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();
        $replyChannel->send($replyMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);
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

        $errorMessage = ErrorMessage::create(MessageHandlingException::create('error occurred'));
        $replyChannelName = 'replyChannel';
        $replyChannel = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $this->expectException(Exception::class);

        $gatewayProxy->getById(1);
    }

    public function test_throwing_exception_when_received_error_message_for_future_reply_sender()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $errorMessage = ErrorMessage::create(MessageHandlingException::create('error occurred'));
        $replyChannelName = 'reply-channel';
        $replyChannel = QueueChannel::create();
        $replyChannel->send($errorMessage);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceWithFutureReceive::class, 'someLongRunningWork', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);
        /** @var ServiceInterfaceWithFutureReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

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

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName);
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

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
        $gatewayProxyBuilder->withReplyChannel($replyChannelName);

        $this->expectException(InvalidArgumentException::class);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

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
        $requestChannel = DirectChannel::create();

        $internalChannel = DirectChannel::create();
        $internalChannel->subscribe(ReplyViaHeadersMessageHandler::createAdditionToPayload(4));
        /** @var ServiceReceivingMessageAndReturningMessage $internalGatewayProxy1 */
        $internalGatewayProxy1 = $this->createGateway($interfaceName, $methodName, $internalChannel);
        $internalChannel = DirectChannel::create();
        $internalChannel->subscribe(ReplyViaHeadersMessageHandler::createAdditionToPayload(2));
        /** @var ServiceReceivingMessageAndReturningMessage $internalGatewayProxy2 */
        $internalGatewayProxy2 = $this->createGateway($interfaceName, $methodName, $internalChannel);
        $requestChannel->subscribe(ReplyViaHeadersMessageHandler::createWithCallback(function (Message $message) use ($internalGatewayProxy1, $internalGatewayProxy2) {
            $result = $internalGatewayProxy1->execute($message);

            return $internalGatewayProxy2->execute($result);
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

    /**
     * @param $interfaceName
     * @param $methodName
     * @param $requestChannel
     * @param null|PollableChannel $replyChannel
     * @return object|RemoteObjectInterface
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function createGateway($interfaceName, $methodName, $requestChannel, ?PollableChannel $replyChannel = null)
    {
        $gatewayProxyBuilder = GatewayProxyBuilder::create('some', $interfaceName, $methodName, 'requestChannel');

        if ($replyChannel) {
            $gatewayProxyBuilder->withReplyChannel('replyChannel');
        }
        return ComponentTestBuilder::create()
            ->withChannel('requestChannel', $requestChannel)
            ->withChannel('replyChannel', $replyChannel ? $replyChannel : QueueChannel::create())
            ->buildWithProxy($gatewayProxyBuilder);
    }

    public function test_propagating_error_to_error_channel()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(ExceptionMessageHandler::create());

        $errorChannel = QueueChannel::create();
        $errorChannelName = 'errorChannel';
        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceReceiveOnly $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($errorChannelName, $errorChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->sendMail();

        $this->assertInstanceOf(
            ErrorMessage::class,
            $errorChannel->receive()
        );
        $this->assertNull($errorChannel->receive());
    }

    public function test_propagating_error_to_error_channel_when_exception_happen_during_receiving_reply()
    {
        $replyChannelName = 'replyChannel';
        $replyChannel = new PollingChannelThrowingException('any');
        $exception = new RuntimeException('some error');
        $replyChannel->withException($exception);

        $requestChannelName = 'requestChannel';
        $requestChannel = QueueChannel::create();

        $errorChannelName = 'errorChannel';
        $errorChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->withChannel($errorChannelName, $errorChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->getById(123);

        /** @var ErrorMessage $errorMessage */
        $errorMessage = $errorChannel->receive();
        $this->assertNotNull($errorMessage, 'Error message was not sent to error channel');
        $this->assertEquals($exception->getMessage(), $errorMessage->getPayload()->getMessage());
    }

    public function test_propagating_error_to_error_channel_when_error_message_received_from_reply_channel()
    {
        $replyChannelName = 'replyChannel';
        $exception = new RuntimeException('Error happened');
        $replyChannel = QueueChannel::create();
        $firstFailedMessage = MessageBuilder::withPayload('some')->build();
        $exception = MessageHandlingException::createWithFailedMessage($exception, $firstFailedMessage);
        $errorMessage = ErrorMessage::create($exception);
        $replyChannel->send($errorMessage);

        $requestChannelName = 'requestChannel';
        $requestChannel = QueueChannel::create();

        $errorChannelName = 'errorChannel';
        $errorChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName)
            ->withErrorChannel($errorChannelName);

        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->withChannel($errorChannelName, $errorChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->getById(123);

        /** @var ErrorMessage $receivedErrorMessage */
        $receivedErrorMessage = $errorChannel->receive();
        $this->assertNotNull($receivedErrorMessage, 'Error message never arrived on error channel');

        $this->assertEquals($exception, $receivedErrorMessage->getPayload()->getCause());
        $this->assertEquals($firstFailedMessage, $receivedErrorMessage->getPayload()->getCause()->getFailedMessage());
    }

    public function test_throwing_root_cause_exception_when_no_error_channel_defined()
    {
        $replyChannelName = 'replyChannel';
        $replyChannel = QueueChannel::create();
        $exception = MessageHandlingException::fromOtherException(new RuntimeException('Error happened'), MessageBuilder::withPayload('some')->build());
        $errorMessage = ErrorMessage::create($exception);
        $replyChannel->send($errorMessage);

        $requestChannelName = 'requestChannel';
        $requestChannel = QueueChannel::create();

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendAndReceive::class, 'getById', $requestChannelName)
            ->withReplyChannel($replyChannelName);

        /** @var ServiceInterfaceSendAndReceive $gatewayProxy */
        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel($replyChannelName, $replyChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $this->expectException(RuntimeException::class);

        $gatewayProxy->getById(123);
    }

    public function test_requesting_with_original_message_and_returning_new_message()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = 'newMessage';
        $requestChannel->subscribe(DataReturningService::createServiceActivator($replyData));

        /** @var ServiceReceivingMessageAndReturningMessage $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy(GatewayProxyBuilder::create('ref-name', ServiceReceivingMessageAndReturningMessage::class, 'execute', $requestChannelName));

        $replyMessage = $gateway->execute(MessageBuilder::withPayload('some')->setHeader('token', '123')->build());

        $this->assertEquals(
            $replyData,
            $replyMessage->getPayload()
        );
    }

    public function test_calling_interface_with_around_interceptor_from_endpoint_annotation()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
            ->withEndpointAnnotations([new AttributeDefinition(Transactional::class, [['transactionFactory']])])
            ->addAroundInterceptor(
                AroundInterceptorBuilder::create('transactionInterceptor', InterfaceToCall::create(TransactionInterceptor::class, 'transactional'), 1, Transactional::class, [])
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('transactionFactory', $transactionFactoryOne)
            ->withReference('transactionInterceptor', $transactionInterceptor)
            ->buildWithProxy($gatewayProxyBuilder);


        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_throwing_exception_when_replacing_argument_in_around_interceptor()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), CalculatingServiceInterceptorExample::create(1), 'sum', 1, ServiceInterfaceCalculatingService::class)
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $this->expectException(InvalidArgumentException::class);

        $gatewayProxy->calculate(5);
    }

    public function test_calling_interface_with_around_interceptor_from_method_annotation()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayMethodExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $transactionInterceptor, 'transactional', 1, Transactional::class)
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('transactionFactory', $transactionFactoryOne)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_from_class_annotation()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $transactionInterceptor, 'transactional', 1, Transactional::class)
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('transactionFactory', $transactionFactoryOne)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_and_choosing_method_annotation_over_class()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassAndMethodExample::class, 'invoke', $requestChannelName)
            ->addAroundInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $transactionInterceptor, 'transactional', 1, Transactional::class)
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('transactionFactory2', $transactionFactoryOne)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_around_interceptor_and_choosing_endpoint_annotation_over_method()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', TransactionalInterceptorOnGatewayClassAndMethodExample::class, 'invoke', $requestChannelName)
            ->withEndpointAnnotations([new AttributeDefinition(Transactional::class, [['transactionFactory0']])])
            ->addAroundInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $transactionInterceptor, 'transactional', 1, Transactional::class)
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('transactionFactory0', $transactionFactoryOne)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->invoke('test');

        $this->assertTrue($transactionOne->isCommitted());
    }

    public function test_calling_interface_with_before_and_after_interceptors()
    {
        $messageHandler = ComponentTestBuilder::create()->build(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'));
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
            ->addBeforeInterceptor(
                MethodInterceptor::create(
                    'interceptor0',
                    InterfaceToCall::create(CalculatingService::class, 'multiply'),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), 'multiply'),
                    0,
                    ''
                )
            )
            ->addBeforeInterceptor(
                MethodInterceptor::create(
                    'interceptor1',
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), 'sum'),
                    1,
                    ''
                )
            )
            ->addAfterInterceptor(
                MethodInterceptor::create(
                    'interceptor2',
                    InterfaceToCall::create(CalculatingService::class, 'result'),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result'),
                    1,
                    ''
                )
            )
            ->addAfterInterceptor(
                MethodInterceptor::create(
                    'interceptor3',
                    InterfaceToCall::create(CalculatingService::class, 'multiply'),
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'),
                    0,
                    ''
                )
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy($gatewayProxyBuilder);

        $this->assertEquals(20, $gatewayProxy->calculate(2));
    }

    public function test_calling_around_interceptors_before_sending_to_error_channel()
    {
        $messageHandler = ExceptionMessageHandler::create();
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe($messageHandler);

        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName)
            ->withErrorChannel('some')
            ->withEndpointAnnotations([new AttributeDefinition(Transactional::class, [['transactionFactory']])])
            ->addAroundInterceptor(
                AroundInterceptorBuilder::create('transactionInterceptor', InterfaceToCall::create(TransactionInterceptor::class, 'transactional'), 1, Transactional::class, [])
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withChannel('some', QueueChannel::create())
            ->withReference('transactionFactory', $transactionFactoryOne)
            ->withReference('transactionInterceptor', $transactionInterceptor)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isRolledBack());
    }

    public function test_calling_interceptors_before_sending_to_error_channel_when_receive_throws_error()
    {
        $requestChannelName = 'request-channel';
        $replyChannel = new PollingChannelThrowingException('any');
        $exception = new RuntimeException();
        $replyChannel->withException($exception);


        $transactionOne = NullTransaction::start();
        $transactionInterceptor = new TransactionInterceptor();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $gatewayProxyBuilder = GatewayProxyBuilder::create('ref-name', ServiceInterfaceReceiveOnlyWithNull::class, 'sendMail', $requestChannelName)
            ->withReplyChannel('replyChannel')
            ->withErrorChannel('some')
            ->withEndpointAnnotations([new AttributeDefinition(Transactional::class, [['transactionFactory']])])
            ->addAroundInterceptor(
                AroundInterceptorBuilder::create('transactionInterceptor', InterfaceToCall::create(TransactionInterceptor::class, 'transactional'), 1, Transactional::class, [])
            );

        $gatewayProxy = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, QueueChannel::create())
            ->withChannel('some', QueueChannel::create())
            ->withChannel('replyChannel', $replyChannel)
            ->withReference('transactionFactory', $transactionFactoryOne)
            ->withReference('transactionInterceptor', $transactionInterceptor)
            ->buildWithProxy($gatewayProxyBuilder);

        $gatewayProxy->sendMail('test');

        $this->assertTrue($transactionOne->isRolledBack());
    }

    public function test_converting_to_string()
    {
        $requestChannelName = 'inputChannel';
        $referenceName = 'ref-name';

        $this->assertEquals(
            GatewayProxyBuilder::create($referenceName, ServiceInterfaceSendOnly::class, 'sendMail', $requestChannelName),
            sprintf('Gateway - %s:%s with reference name `%s` for request channel `%s`', ServiceInterfaceSendOnly::class, 'sendMail', $referenceName, $requestChannelName)
        );
    }

    public function test_throwing_exception_if_creating_gateway_with_error_channel_and_interface_can_not_return_null()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withChannel('requestChannel', DirectChannel::create())
            ->withChannel('errorChannel', QueueChannel::create())
            ->build(
                GatewayProxyBuilder::create('some', ServiceInterfaceReceiveOnly::class, 'sendMail', 'requestChannel')
                    ->withErrorChannel('errorChannel')
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_using_message_converter_for_transformation_according_to_interface()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = MessageBuilder::withPayload('newMessage')->build();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($messageHandler);

        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', FakeMessageConverterGatewayExample::class, 'execute', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderBuilder::create('some', 'some'),
                GatewayPayloadBuilder::create('amount'),
            ])
            ->withMessageConverters([
                'converter',
            ]);

        /** @var FakeMessageConverterGatewayExample $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('converter', new FakeMessageConverter())
            ->buildWithProxy($gatewayBuilder);

        $this->assertEquals(
            new stdClass(),
            $gateway->execute([], 100)
        );
    }

    public function test_returning_in_specific_expected_format()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = MessageBuilder::withPayload([1, 2, 3])->build();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($messageHandler);

        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', StringReturningGateway::class, 'execute', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderBuilder::create('replyMediaType', MessageHeaders::REPLY_CONTENT_TYPE),
            ]);

        /** @var StringReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new ArrayToJsonConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $this->assertEquals(
            '[1,2,3]',
            $gateway->execute(MediaType::APPLICATION_JSON)
        );
    }

    public function test_converting_reply_from_one_returned_media_type_to_another()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = MessageBuilder::withPayload([1, 2, 3])->build();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($messageHandler);

        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'execute', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderBuilder::create('replyMediaType', MessageHeaders::REPLY_CONTENT_TYPE),
            ]);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new ArrayToJsonConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $message = $gateway->execute(MediaType::APPLICATION_JSON);
        $this->assertEquals('[1,2,3]', $message->getPayload());
        $this->assertEquals(MediaType::APPLICATION_JSON, $message->getHeaders()->getContentType()->toString());
    }

    public function test_returning_with_specific_content_type_if_defined_in_reply_message()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage('[1,2,3]', [MessageHeaders::CONTENT_TYPE => MediaType::APPLICATION_JSON]));

        $expectedMediaType = MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY)->toString();
        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeNoParameter', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderValueBuilder::create(MessageHeaders::REPLY_CONTENT_TYPE, $expectedMediaType),
            ]);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new JsonToArrayConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $message = $gateway->executeNoParameter();
        $this->assertEquals([1, 2, 3], $message->getPayload());
        $this->assertEquals($expectedMediaType, $message->getHeaders()->getContentType()->toString());
    }

    public function test_returning_with_specific_content_type_based_on_invoked_interface_return_type_when_array()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(
            DataReturningService::createServiceActivatorWithReturnMessage([new stdClass(), new stdClass()], [
                MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::createCollection(stdClass::class)->toString()),
            ])
        );

        $expectedMediaType = MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY)->toString();
        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', MixedReturningGateway::class, 'executeNoParameter', $requestChannelName)
            ->withParameterConverters([
                GatewayHeaderValueBuilder::create(MessageHeaders::REPLY_CONTENT_TYPE, $expectedMediaType),
            ]);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(
                ConversionService::REFERENCE_NAME,
                InMemoryConversionService::createWithoutConversion()
                    ->registerConversion(
                        [new stdClass(), new stdClass()],
                        MediaType::APPLICATION_X_PHP,
                        TypeDescriptor::createCollection(stdClass::class)->toString(),
                        MediaType::APPLICATION_X_PHP_ARRAY,
                        TypeDescriptor::ARRAY,
                        [1, 1]
                    )
            )
            ->buildWithProxy($gatewayBuilder);

        $this->assertEquals([1, 1], $gateway->executeNoParameter());
    }

    public function test_converting_from_reply_header_type_to_expected_one()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage([
            Uuid::fromString('e7019549-9733-45a3-b088-783de2b2357f'),
            Uuid::fromString('30ae8690-c729-447c-b9f9-bafd668cb01e'),
        ], [MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPWithTypeParameter("array<Ramsey\Uuid\Uuid>")->toString()]));

        $expectedMediaType = MediaType::createApplicationXPHPWithTypeParameter('array<string>')->toString();
        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeNoParameter', $requestChannelName)
            ->withReplyContentType($expectedMediaType);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new UuidToStringConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $message = $gateway->executeNoParameter();
        $this->assertNotInstanceOf(Uuid::class, $message->getPayload()[0]);
        $this->assertEquals(['e7019549-9733-45a3-b088-783de2b2357f', '30ae8690-c729-447c-b9f9-bafd668cb01e'], $message->getPayload());
        $this->assertEquals($expectedMediaType, $message->getHeaders()->getContentType()->toString());
    }

    public function test_forcing_conversion_when_target_type_is_array_and_source_is_also_array()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage([
            Uuid::fromString('e7019549-9733-45a3-b088-783de2b2357f'),
            Uuid::fromString('30ae8690-c729-447c-b9f9-bafd668cb01e'),
        ], [MessageHeaders::CONTENT_TYPE => MediaType::APPLICATION_X_PHP_ARRAY]));

        $expectedMediaType = MediaType::APPLICATION_X_PHP_ARRAY;
        /** @var MessageReturningGateway $gateway */
        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeNoParameter', $requestChannelName)
            ->withReplyContentType($expectedMediaType);

        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new ExampleStdClassConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $message = $gateway->executeNoParameter();
        $this->assertIsNotArray($message->getPayload());
        $this->assertNotInstanceOf(Uuid::class, $message->getPayload());
    }

    public function test_converting_according_to_interface_to_call_return_type()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivator('e7019549-9733-45a3-b088-783de2b2357f'));

        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', UuidReturningGateway::class, 'executeNoParameter', $requestChannelName);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new StringToUuidConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $this->assertEquals(Uuid::fromString('e7019549-9733-45a3-b088-783de2b2357f'), $gateway->executeNoParameter());
    }

    public function test_not_converting_when_return_type_is_message()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage([1, 2, 3], [
            MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPWithTypeParameter('array')->toString(),
        ]));

        $gatewayBuilder = GatewayProxyBuilder::create('ref-name', BeforeSendGateway::class, 'execute', $requestChannelName);

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference(ConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([new ExceptionalConverter()]))
            ->buildWithProxy($gatewayBuilder);

        $this->assertInstanceOf(Message::class, $gateway->execute(MessageBuilder::withPayload('b')->build()));
    }

    public function test_not_converting_if_reply_has_already_expected_type()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = '[1,2,3]';
        $mediaType = MediaType::APPLICATION_JSON;
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage($replyData, [MessageHeaders::CONTENT_TYPE => $mediaType]));

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy(GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeNoParameter', $requestChannelName)
                ->withReplyContentType($mediaType));

        $replyMessage = $gateway->executeNoParameter(MessageBuilder::withPayload('some')->setHeader('token', '123')->build());

        $this->assertEquals(
            $replyData,
            $replyMessage->getPayload()
        );
        $this->assertEquals(
            $mediaType,
            $replyMessage->getHeaders()->get(MessageHeaders::CONTENT_TYPE)
        );
    }

    public function test_throwing_exception_if_converter_fro_reply_media_type_is_missing()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = 2;
        $requestChannel->subscribe(DataReturningService::createServiceActivator($replyData));

        /** @var MessageReturningGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->buildWithProxy(GatewayProxyBuilder::create('ref-name', UuidReturningGateway::class, 'executeNoParameter', $requestChannelName)
                ->withReplyContentType(MediaType::APPLICATION_JSON));

        $this->expectException(InvalidArgumentException::class);

        $gateway->executeNoParameter();
    }

    public function test_executing_interface_with_parameters_and_instead_of_parameters_message_is_given()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = '[1,2,3]';
        $mediaType = MediaType::APPLICATION_JSON;
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage($replyData, [MessageHeaders::CONTENT_TYPE => $mediaType]));

        /** @var NonProxyGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->build(GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeWithMetadataWithDefault', $requestChannelName)
                ->withReplyContentType($mediaType));

        $replyMessage = $gateway->execute([MessageBuilder::withPayload('some')->build()]);

        $this->assertEquals(
            $replyData,
            $replyMessage->getPayload()
        );
    }

    public function test_executing_with_default_parameters_auto_resolved()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = '[1,2,3]';
        $mediaType = MediaType::APPLICATION_JSON;
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage($replyData, [MessageHeaders::CONTENT_TYPE => $mediaType]));

        /** @var NonProxyGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->build(GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeWithMetadataWithDefault', $requestChannelName)
                ->withReplyContentType($mediaType));

        $replyMessage = $gateway->execute([['some']]);

        $this->assertEquals(
            $replyData,
            $replyMessage->getPayload()
        );
    }

    public function test_executing_with_passed_null()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $replyData = '[1,2,3]';
        $mediaType = MediaType::APPLICATION_JSON;
        $requestChannel->subscribe(DataReturningService::createServiceActivatorWithReturnMessage($replyData, [MessageHeaders::CONTENT_TYPE => $mediaType]));

        /** @var NonProxyGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->build(GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeWithMetadataWithNull', $requestChannelName)
                ->withParameterConverters([
                    GatewayHeaderBuilder::create('metadata', 'someData'),
                ])
                ->withReplyContentType($mediaType));

        /** @var Message $replyMessage */
        $replyMessage = $gateway->execute([['some'], null]);

        $this->assertFalse($replyMessage->getHeaders()->containsKey('someData'));
    }

    public function test_throwing_exception_when_there_is_not_enough_parameters_given()
    {
        $requestChannelName = 'request-channel';
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(DataReturningService::createServiceActivator('test'));
        $mediaType = MediaType::APPLICATION_JSON;

        /** @var NonProxyGateway $gateway */
        $gateway = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->build(GatewayProxyBuilder::create('ref-name', MessageReturningGateway::class, 'executeWithMetadata', $requestChannelName)
                ->withReplyContentType($mediaType));

        $this->expectException(InvalidArgumentException::class);

        $gateway->execute([]);
    }
}
