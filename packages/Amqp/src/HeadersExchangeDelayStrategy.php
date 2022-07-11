<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Enqueue\AmqpTools\DelayStrategy;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpTopic;

class HeadersExchangeDelayStrategy implements DelayStrategy
{
    /**
     * {@inheritdoc}
     */
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message, int $delay): void
    {
        $properties = $message->getProperties();

        // The x-death header must be removed because of the bug in RabbitMQ.
        // It was reported that the bug is fixed since 3.5.4 but I tried with 3.6.1 and the bug still there.
        // https://github.com/rabbitmq/rabbitmq-server/issues/216
        unset($properties['x-death']);


        $afterDelayDestination = '';
        if ($dest instanceof AmqpTopic) {
            $afterDelayDestination = $dest->getTopicName();
        }

        $delayMessage = $context->createMessage($message->getBody(), $properties, $message->getHeaders());
        $delayMessage->setRoutingKey($message->getRoutingKey());
        $delayMessage->setProperty('ecotone_delay', $delay);
        $delayMessage->setHeader('ecotone_delay', $delay);

        $exchange = AmqpExchange::createHeadersExchange('ecotone_delay');

        $queue = \Ecotone\Amqp\AmqpQueue::createWith('ecotone_' . $delay . '_delay');
        $queue->withArgument('x-message-ttl', $delay);
        $queue->withArgument('x-dead-letter-exchange', $afterDelayDestination);

        $binding = AmqpBinding::createHeadersBinding($exchange, $queue, ['ecotone_delay' => $delay]);

        $enqueueExchange = $exchange->toEnqueueExchange();
        $context->declareTopic($enqueueExchange);

        $context->declareQueue($queue->toEnqueueQueue());
        $context->bind($binding->toEnqueueBinding());

        $context->createProducer()->send($enqueueExchange, $delayMessage);
    }
}
