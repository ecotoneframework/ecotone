<?php
require __DIR__."/vendor/autoload.php";

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Enqueue\Consumption\QueueConsumer;
use Interop\Queue\Message;

$config = [
    "dsn" => "amqp://rabbitmq:5672",
    "qos_prefetch_count" => 5
];
$factory = new AmqpConnectionFactory($config);

// default
$context = $factory->createContext();

//$producer = $context->createProducer();
//$destination = $context->createQueue("x");
//$context->declareQueue($destination);
//$producer->send($destination, $context->createMessage("some"));
//$result = $factory->createContext()->createConsumer($destination)->receive();
//var_dump($result);die();


//$destination = $context->createQueue('foo');
//$context->declareQueue($destination);
//
////$destination = $context->createTopic('foo');
//
//$message = $context->createMessage('Hello world!');
//
//$context->createProducer()->send($destination, $message);
//$consumer = $context->createConsumer($destination);
//
//$message = $consumer->receive(0);
//$acker = \SimplyCodedSoftware\Amqp\EnqueueAcknowledgementCallback::createWithAutoAck(
//    $consumer, $message
//);
//var_dump($message->getContentType());

// ------


$destination = $context->createTopic("rabbit");
$context->declareTopic($destination);

$queue = $context->createQueue("");
$context->declareQueue($queue);

$context->bind(new AmqpBind($destination, $queue, "some"));

$producer = $context->createProducer();
$message = $context->createMessage("some");
$message->setRoutingKey("some");

$producer->send($destination, $message);

$consumer = $context->createConsumer($queue);
//var_dump($message = $consumer->receiveNoWait());
//$consumer->acknowledge($message);
//$consumer->receive();

$subscriptionConsumer = $context->createSubscriptionConsumer();
$subscriptionConsumer->subscribe($consumer, function(Message $message, \Interop\Queue\Consumer $consumer) {
    var_dump($message);
});
$subscriptionConsumer->consume(1);

//$queueConsumer = new QueueConsumer($context);
//
//$queueConsumer->bindCallback('foo', function(Message $message) {
//    // process message
//var_dump($message);
//    return \Interop\Queue\Processor::ACK;
//});
//
//$queueConsumer->consume();