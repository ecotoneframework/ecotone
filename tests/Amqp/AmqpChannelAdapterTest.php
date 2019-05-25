<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Amqp;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Amqp\AmqpAcknowledgeConfirmationInterceptor;
use SimplyCodedSoftware\Amqp\AmqpAdmin;
use SimplyCodedSoftware\Amqp\AmqpBackedMessageChannelBuilder;
use SimplyCodedSoftware\Amqp\AmqpBackendMessageChannel;
use SimplyCodedSoftware\Amqp\AmqpBinding;
use SimplyCodedSoftware\Amqp\AmqpExchange;
use SimplyCodedSoftware\Amqp\AmqpHeader;
use SimplyCodedSoftware\Amqp\AmqpQueue;
use SimplyCodedSoftware\Amqp\AmqpInboundChannelAdapterBuilder;
use SimplyCodedSoftware\Amqp\AmqpOutboundChannelAdapterBuilder;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use SimplyCodedSoftware\Messaging\Conversion\ObjectToSerialized\SerializingConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use SimplyCodedSoftware\Messaging\Endpoint\AcknowledgementCallback;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ForwardMessageHandler;

/**
 * Class InboundAmqpGatewayBuilder
 * @package Test\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpChannelAdapterTest extends AmqpMessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_to_default_exchange_with_routing_by_queue_name()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($queueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $messageToSend               = MessageBuilder::withPayload("some")->build();
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withDefaultRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);
        $message            = $this->receiveOnce($inboundAmqpAdapter, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService);

        $this->assertNotNull($message, "Message was not received from rabbit");

        $this->assertEquals(
            $message->getPayload(),
            "some"
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_sending_non_string_payload_without_media_type_information()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [AmqpQueue::createWith($queueName)->withExclusivity()];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $messageToSend               = MessageBuilder::withPayload(new \stdClass())->build();
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName);

        $this->expectException(InvalidArgumentException::class);

        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_sending_non_string_payload_with_media_type_but_no_converter_available()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [AmqpQueue::createWith($queueName)->withExclusivity()];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $messageToSend               = MessageBuilder::withPayload(new \stdClass())
                                            ->setContentType(MediaType::createApplicationXPHPObject())
                                            ->build();
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName);

        $this->expectException(InvalidArgumentException::class);

        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_converting_payload_to_string_if_converter_exists_and_media_type_passed()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [AmqpQueue::createWith($queueName)->withExclusivity()];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $payload                     = new \stdClass();
        $payload->name = "someName";
        $messageToSend               = MessageBuilder::withPayload($payload)
                                        ->setContentType(MediaType::createApplicationXPHPObject())
                                        ->build();
        $converters                  = [new SerializingConverter()];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withDefaultRoutingKey($queueName);

        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);


        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);
        $message            = $this->receiveOnce($inboundAmqpAdapter, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService);

        $this->assertNotNull($message, "Message was not received from rabbit");
        $this->assertEquals(
            $message->getPayload(),
            serialize($payload)
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_not_receiving_a_message_when_queue_is_empty()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($queueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $inboundAmqpGatewayBuilder = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);

        $this->assertNull($this->receiveOnce($inboundAmqpGatewayBuilder, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_and_receiving_with_routing_key_to_custom_exchange()
    {
        $exchangeName                = Uuid::uuid4()->toString();
        $whiteQueueName              = Uuid::uuid4()->toString();
        $blackQueueName              = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($blackQueueName)
                ->withExclusivity(),
            AmqpQueue::createWith($whiteQueueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [
            AmqpExchange::createDirectExchange($exchangeName)
                ->withAutoDeletion()
        ];
        $amqpBindings                = [
            AmqpBinding::createFromNames($exchangeName, $whiteQueueName, "white"),
            AmqpBinding::createFromNames($exchangeName, $blackQueueName, "black")
        ];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);


        $inboundAmqpAdapterForBlack = $this->createAmqpInboundAdapter($blackQueueName, $requestChannelName, $amqpConnectionReferenceName);
        $inboundAmqpAdapterForWhite = $this->createAmqpInboundAdapter($whiteQueueName, $requestChannelName, $amqpConnectionReferenceName);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withDefaultRoutingKey("white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNull($this->receiveOnce($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receiveOnce($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_and_receiving_from_fanout_exchange()
    {
        $exchangeName                = Uuid::uuid4()->toString();
        $whiteQueueName              = Uuid::uuid4()->toString();
        $blackQueueName              = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($blackQueueName)
                ->withExclusivity(),
            AmqpQueue::createWith($whiteQueueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [
            AmqpExchange::createFanoutExchange($exchangeName)
                ->withAutoDeletion()
        ];
        $amqpBindings                = [
            AmqpBinding::createFromNamesWithoutRoutingKey($exchangeName, $whiteQueueName),
            AmqpBinding::createFromNamesWithoutRoutingKey($exchangeName, $blackQueueName)
        ];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);


        $inboundAmqpAdapterForBlack = $this->createAmqpInboundAdapter($blackQueueName, $requestChannelName, $amqpConnectionReferenceName);
        $inboundAmqpAdapterForWhite = $this->createAmqpInboundAdapter($whiteQueueName, $requestChannelName, $amqpConnectionReferenceName);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withDefaultRoutingKey("white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNotNull($this->receiveOnce($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receiveOnce($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_and_receiving_from_topic_exchange()
    {
        $exchangeName                = Uuid::uuid4()->toString();
        $whiteQueueName              = Uuid::uuid4()->toString();
        $blackQueueName              = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($blackQueueName)
                ->withExclusivity(),
            AmqpQueue::createWith($whiteQueueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [
            AmqpExchange::createTopicExchange($exchangeName)
                ->withAutoDeletion()
        ];
        $amqpBindings                = [
            AmqpBinding::createFromNames($exchangeName, $whiteQueueName, "*.white"),
            AmqpBinding::createFromNames($exchangeName, $blackQueueName, "*.black")
        ];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);


        $inboundAmqpAdapterForBlack = $this->createAmqpInboundAdapter($blackQueueName, $requestChannelName, $amqpConnectionReferenceName);
        $inboundAmqpAdapterForWhite = $this->createAmqpInboundAdapter($whiteQueueName, $requestChannelName, $amqpConnectionReferenceName);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withDefaultRoutingKey("color.white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNull($this->receiveOnce($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receiveOnce($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_and_receiving_with_header_mapping()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [
            AmqpQueue::createWith($queueName)
                ->withExclusivity()
        ];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = QueueChannel::create();
        $amqpConnectionReferenceName = "connection";
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $messageToSend              = MessageBuilder::withPayload("some")
            ->setHeader("token", "123")
            ->setHeader("userId", 2)
            ->setHeader("userName", "Johny")
            ->setHeader("userSurname", "Casa")
            ->build();
        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withHeaderMapper("token,user*")
            ->withDefaultRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName)
            ->withHeaderMapper("token, userName");
        $message            = $this->receiveOnce($inboundAmqpAdapter, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService);

        $this->assertNotNull($message, "Message was not received from rabbit");

        $this->assertEquals("123", $message->getHeaders()->get("token"));
        $this->assertEquals("Johny", $message->getHeaders()->get("userName"));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_sending_message_with_auto_acking()
    {
        $queueName                   = Uuid::uuid4()->toString();
        $amqpQueues                  = [AmqpQueue::createWith($queueName)->withExclusivity()];
        $amqpExchanges               = [];
        $amqpBindings                = [];
        $requestChannelName          = "requestChannel";
        $inboundRequestChannel       = DirectChannel::create();
        $amqpConnectionReferenceName = "connection";
        $messageToSend               = MessageBuilder::withPayload("some")->build();
        $converters                  = [];
        $inMemoryChannelResolver     = $this->createChannelResolver($requestChannelName, $inboundRequestChannel);
        $referenceSearchService      = $this->createReferenceSearchService($amqpConnectionReferenceName, $amqpExchanges, $amqpQueues, $amqpBindings, $converters);

        $outboundAmqpGatewayBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withDefaultRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);

        $inboundQueueChannel = QueueChannel::create();
        $inboundRequestChannel->subscribe(ForwardMessageHandler::create($inboundQueueChannel));

        $inboundAmqpGateway = $inboundAmqpAdapter
            ->build($inMemoryChannelResolver, $referenceSearchService, PollingMetadata::create("")->setHandledMessageLimit(1));
        $inboundAmqpGateway->run();

        $this->assertNotNull($this->receiveOnce($inboundAmqpAdapter, $inboundQueueChannel, $inMemoryChannelResolver, $referenceSearchService), "Message was not requeued correctly");

        $this->assertNull($this->receiveOnce($inboundAmqpAdapter, $inboundQueueChannel, $inMemoryChannelResolver, $referenceSearchService), "Message was not acked correctly");
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_receiving_message_second_time_when_requeued()
    {
        $queueName                   = Uuid::uuid4()->toString();

        $amqpBackedMessageChannel = $this->createAmqpBackendMessageChannel($queueName);
        $amqpBackedMessageChannel->send(MessageBuilder::withPayload("some")->build());

        /** @var Message $message */
        $message = $amqpBackedMessageChannel->receive();

        /** @var AcknowledgementCallback $acknowledgeCallback */
        $acknowledgeCallback = $message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE);
        $acknowledgeCallback->requeue();

        $this->assertNotNull($amqpBackedMessageChannel->receive());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_not_receiving_message_second_time_when_acked()
    {
        $queueName                   = Uuid::uuid4()->toString();

        $amqpBackedMessageChannel = $this->createAmqpBackendMessageChannel($queueName);
        $amqpBackedMessageChannel->send(MessageBuilder::withPayload("some")->build());

        /** @var Message $message */
        $message = $amqpBackedMessageChannel->receive();

        /** @var AcknowledgementCallback $acknowledgeCallback */
        $acknowledgeCallback = $message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE);
        $acknowledgeCallback->accept();

        $this->assertNull($amqpBackedMessageChannel->receive());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_not_receiving_message_second_time_when_rejected()
    {
        $queueName                   = Uuid::uuid4()->toString();

        $amqpBackedMessageChannel = $this->createAmqpBackendMessageChannel($queueName);
        $amqpBackedMessageChannel->send(MessageBuilder::withPayload("some")->build());

        /** @var Message $message */
        $message = $amqpBackedMessageChannel->receive();

        /** @var AcknowledgementCallback $acknowledgeCallback */
        $acknowledgeCallback = $message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE);
        $acknowledgeCallback->reject();

        $this->assertNull($amqpBackedMessageChannel->receive());
    }

    /**
     * @param string         $requestChannelName
     * @param MessageChannel $inboundRequestChannel
     *
     * @return InMemoryChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createChannelResolver(string $requestChannelName, MessageChannel $inboundRequestChannel): InMemoryChannelResolver
    {
        $channelResolver = InMemoryChannelResolver::createFromAssociativeArray(
            [
                $requestChannelName => $inboundRequestChannel
            ]
        );

        return $channelResolver;
    }

    /**
     * @param string $amqpConnectionReferenceName
     * @param array  $amqpExchanges
     * @param array  $amqpQueues
     * @param array  $amqpBindings
     * @param array  $converters
     *
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createReferenceSearchService(string $amqpConnectionReferenceName, array $amqpExchanges, array $amqpQueues, array $amqpBindings, array $converters): InMemoryReferenceSearchService
    {
        $referenceSearchService = InMemoryReferenceSearchService::createWith(
            [
                $amqpConnectionReferenceName => $this->getCachedConnectionFactory(),
                AmqpAdmin::REFERENCE_NAME => AmqpAdmin::createWith($amqpExchanges, $amqpQueues, $amqpBindings),
                ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters)
            ]
        );

        return $referenceSearchService;
    }

    /**
     * @param AmqpOutboundChannelAdapterBuilder             $outboundAmqpGatewayBuilder
     * @param ChannelResolver                        $channelResolver
     * @param ReferenceSearchService                 $referenceSearchService
     * @param \SimplyCodedSoftware\Messaging\Message $messageToSend
     *
     * @return void
     * @throws \Exception
     */
    private function send(AmqpOutboundChannelAdapterBuilder $outboundAmqpGatewayBuilder, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, \SimplyCodedSoftware\Messaging\Message $messageToSend)
    {
        $outboundAmqpGatewayBuilder
            ->withAutoDeclareOnSend(true)
            ->build($channelResolver, $referenceSearchService)->handle($messageToSend);
    }

    /**
     * @param AmqpInboundChannelAdapterBuilder $inboundAmqpGatewayBuilder
     * @param QueueChannel              $inboundRequestChannel
     * @param ChannelResolver           $channelResolver
     * @param ReferenceSearchService    $referenceSearchService
     *
     * @return Message|null
     */
    private function receiveOnce(AmqpInboundChannelAdapterBuilder $inboundAmqpGatewayBuilder, QueueChannel $inboundRequestChannel, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): ?Message
    {
        return $this->receiveWithPollingMetadata($inboundAmqpGatewayBuilder, $inboundRequestChannel, $channelResolver, $referenceSearchService, PollingMetadata::create("someId")->setExecutionAmountLimit(1));
    }

    /**
     * @param AmqpInboundChannelAdapterBuilder $inboundAmqpGatewayBuilder
     * @param QueueChannel $inboundRequestChannel
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     *
     * @param PollingMetadata $pollingMetadata
     * @return Message|null
     */
    private function receiveWithPollingMetadata(AmqpInboundChannelAdapterBuilder $inboundAmqpGatewayBuilder, QueueChannel $inboundRequestChannel, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ?Message
    {
        $inboundAmqpGateway = $inboundAmqpGatewayBuilder
            ->build($channelResolver, $referenceSearchService, $pollingMetadata);
        $inboundAmqpGateway->run();

        $message = $inboundRequestChannel->receive();

        return $message;
    }

    /**
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     *
     * @return AmqpInboundChannelAdapterBuilder
     * @throws \Exception
     */
    private function createAmqpInboundAdapter(string $queueName, string $requestChannelName, string $amqpConnectionReferenceName): AmqpInboundChannelAdapterBuilder
    {
        return AmqpInboundChannelAdapterBuilder::createWith(
            Uuid::uuid4()->toString(),
            $queueName,
            $requestChannelName,
            $amqpConnectionReferenceName
        )
            ->withReceiveTimeout(1);
    }

    /**
     * @param string $queueName
     * @return AmqpBackendMessageChannel
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createAmqpBackendMessageChannel(string $queueName) : AmqpBackendMessageChannel
    {
        $amqpConnectionReferenceName = "amqpConnectionName";
        $referenceSearchService = $this->createReferenceSearchService(
            $amqpConnectionReferenceName,
            [],
            [AmqpQueue::createWith($queueName)],
            [],
            []
        );

        return AmqpBackedMessageChannelBuilder::create($queueName, $amqpConnectionReferenceName)
                ->withReceiveTimeout(1)
                ->build($referenceSearchService);
    }
}