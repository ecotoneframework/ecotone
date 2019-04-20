<?php
require __DIR__."/vendor/autoload.php";

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Enqueue\Consumption\QueueConsumer;
use Interop\Queue\Message;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Amqp\AmqpAdmin;
use SimplyCodedSoftware\Amqp\InboundAmqpGatewayBuilder;
use SimplyCodedSoftware\Amqp\OutboundAmqpGatewayBuilder;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

$factory = new AmqpConnectionFactory(["dsn" => "amqp://rabbitmq:5672", "lazy" => false]);

// default
$context = $factory->createContext();
//\PHPUnit\Framework\TestCase::assertNotEquals($factory->createContext(), $factory->createContext());


$queueConsumer = new QueueConsumer($context, new \Enqueue\Consumption\ChainExtension([
    new \Enqueue\Consumption\Extension\SignalExtension(),
    new \Enqueue\Consumption\Extension\LimitConsumptionTimeExtension(new \DateTime('now + 60 sec'))
]));

$queueConsumer->bindCallback('test_queue', function(Message $message) {
    // process message

    return \Interop\Queue\Processor::ACK;
});

echo "starts\n";
for ($i = 0; $i < 5; $i++) {
    echo "{$i}/5\n";
    $queueConsumer->consume();
}
echo "done\n";
die("bla");




$queueName                   = "test_queue";
$amqpQueues                  = [\SimplyCodedSoftware\Amqp\AmqpQueue::createWith($queueName)];
$amqpExchanges               = [];
$amqpBindings                = [];
$requestChannelName          = "requestChannel";
$inboundRequestChannel       = QueueChannel::create();
$amqpConnectionReferenceName = "connection";
$messageToSend               = MessageBuilder::withPayload("some")->build();
$converters                  = [];
$inMemoryChannelResolver     = $channelResolver = InMemoryChannelResolver::createFromAssociativeArray(
    [
        $requestChannelName => $inboundRequestChannel
    ]
);
$referenceSearchService      =         $referenceSearchService = InMemoryReferenceSearchService::createWith(
    [
        $amqpConnectionReferenceName => $factory,
        AmqpAdmin::REFERENCE_NAME => AmqpAdmin::createWith($amqpExchanges, $amqpQueues, $amqpBindings),
        ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters)
    ]
);

$outboundAmqpGatewayBuilder = OutboundAmqpGatewayBuilder::createForDefaultExchange($amqpConnectionReferenceName)
    ->withDefaultRoutingKey($queueName);
$outboundAmqpGatewayBuilder
    ->withAutoDeclareOnSend(true)
    ->build($channelResolver, $referenceSearchService)
    ->handle($messageToSend);

$inboundAmqpAdapter = InboundAmqpGatewayBuilder::createWith(
    Uuid::uuid4()->toString(),
    $queueName,
    $requestChannelName,
    $amqpConnectionReferenceName
);
//  $inboundAmqpAdapter
//    ->withReceiveTimeout(1);
$inboundAmqpGateway = $inboundAmqpAdapter
    ->build($channelResolver, $referenceSearchService);

echo "starts\n";
for ($i = 0; $i < 5; $i++) {
    echo "{$i}/5\n";
    $inboundAmqpGateway->run();
}
echo "done\n";
$message = $inboundRequestChannel->receive();

\PHPUnit\Framework\Assert::assertNotNull($message, "Message was not received from rabbit");

\PHPUnit\Framework\Assert::assertEquals(
    $message->getPayload(),
    "some"
);