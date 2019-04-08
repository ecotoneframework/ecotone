<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Amqp;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Amqp\AmqpAdmin;
use SimplyCodedSoftware\Amqp\AmqpBinding;
use SimplyCodedSoftware\Amqp\AmqpExchange;
use SimplyCodedSoftware\Amqp\AmqpQueue;
use SimplyCodedSoftware\Amqp\InboundAmqpGatewayBuilder;
use SimplyCodedSoftware\Amqp\OutboundAmqpGatewayBuilder;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ForwardMessageHandler;

/**
 * Class InboundAmqpGatewayBuilder
 * @package Test\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpGatewayBuilderTest extends AmqpMessagingTest
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

        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);
        $message            = $this->receive($inboundAmqpAdapter, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService);

        $this->assertNotNull($message, "Message was not received from rabbit");

        $this->assertEquals(
            $message->getPayload(),
            "some"
        );
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
     * @param OutboundAmqpGatewayBuilder             $outboundAmqpGatewayBuilder
     * @param ChannelResolver                        $channelResolver
     * @param ReferenceSearchService                 $referenceSearchService
     * @param \SimplyCodedSoftware\Messaging\Message $messageToSend
     *
     * @return void
     * @throws \Exception
     */
    private function send(OutboundAmqpGatewayBuilder $outboundAmqpGatewayBuilder, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, \SimplyCodedSoftware\Messaging\Message $messageToSend)
    {
        $outboundAmqpGatewayBuilder
            ->withAutoDeclareOnSend(true)
            ->build($channelResolver, $referenceSearchService)->handle($messageToSend);
    }

    /**
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     *
     * @return InboundAmqpGatewayBuilder
     * @throws \Exception
     */
    private function createAmqpInboundAdapter(string $queueName, string $requestChannelName, string $amqpConnectionReferenceName): InboundAmqpGatewayBuilder
    {
        return InboundAmqpGatewayBuilder::createWith(
            Uuid::uuid4()->toString(),
            $queueName,
            $requestChannelName,
            $amqpConnectionReferenceName
        )
            ->withReceiveTimeout(1);
    }

    /**
     * @param InboundAmqpGatewayBuilder $inboundAmqpGatewayBuilder
     * @param QueueChannel              $inboundRequestChannel
     * @param ChannelResolver           $channelResolver
     * @param ReferenceSearchService    $referenceSearchService
     *
     * @return Message|null
     */
    public function receive(InboundAmqpGatewayBuilder $inboundAmqpGatewayBuilder, QueueChannel $inboundRequestChannel, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): ?Message
    {
        $inboundAmqpGateway = $inboundAmqpGatewayBuilder
            ->build($channelResolver, $referenceSearchService);
        $inboundAmqpGateway->start();

        $message = $inboundRequestChannel->receive();

        return $message;
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

        $this->assertNull($this->receive($inboundAmqpGatewayBuilder, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
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

        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withRoutingKey("white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNull($this->receive($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receive($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
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

        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withRoutingKey("white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNotNull($this->receive($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receive($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
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

        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::create($exchangeName, $amqpConnectionReferenceName)
            ->withRoutingKey("color.white");
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, MessageBuilder::withPayload("some")->build());

        $this->assertNull($this->receive($inboundAmqpAdapterForBlack, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
        $this->assertNotNull($this->receive($inboundAmqpAdapterForWhite, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService));
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
        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withHeaderMapper("token,user*")
            ->withRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName)
            ->withHeaderMapper("token, userName");
        $message            = $this->receive($inboundAmqpAdapter, $inboundRequestChannel, $inMemoryChannelResolver, $referenceSearchService);

        $this->assertNotNull($message, "Message was not received from rabbit");

        $this->assertEquals("123", $message->getHeaders()->get("token"));
        $this->assertEquals("Johny", $message->getHeaders()->get("userName"));
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __test_sending_message_with_auto_acking()
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

        $outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withRoutingKey($queueName);
        $this->send($outboundAmqpGatewayBuilder, $inMemoryChannelResolver, $referenceSearchService, $messageToSend);

        $inboundAmqpAdapter = $this->createAmqpInboundAdapter($queueName, $requestChannelName, $amqpConnectionReferenceName);

        $exceptionalHandler = ExceptionMessageHandler::create();
        $inboundRequestChannel->subscribe($exceptionalHandler);
        $inboundAmqpGateway = $inboundAmqpAdapter
            ->build($inMemoryChannelResolver, $referenceSearchService);
        $inboundAmqpGateway->start();

        $inboundRequestChannel->unsubscribe($exceptionalHandler);
        $inboundQueueChannel = QueueChannel::create();
        $inboundRequestChannel->subscribe(ForwardMessageHandler::create($inboundQueueChannel));
        $this->assertNotNull($this->receive($inboundAmqpAdapter, $inboundQueueChannel, $inMemoryChannelResolver, $referenceSearchService), "Message was not requeued correctly");

        $this->assertNull($this->receive($inboundAmqpAdapter, $inboundQueueChannel, $inMemoryChannelResolver, $referenceSearchService), "Message was not acked correctly");
    }
}