<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue as EnqueueQueue;
use Interop\Amqp\AmqpTopic as EnqueueExchange;

/**
 * Class AmqpAdmin
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpAdmin
{
    public const REFERENCE_NAME = 'amqp_admin';

    /**
     * @var EnqueueExchange[]
     */
    private $enqueueExchanges = [];
    /**
     * @var EnqueueQueue[]
     */
    private $enqueueQueues = [];
    /**
     * @var AmqpBinding[]
     */
    private $amqpBindings = [];

    /**
     * AmqpAdmin constructor.
     * @param iterable|EnqueueExchange[] $amqpTopics
     * @param iterable|EnqueueQueue[] $amqpQueues
     * @param iterable|AmqpBinding[] $amqpBindings
     */
    private function __construct(iterable $amqpTopics, iterable $amqpQueues, iterable $amqpBindings)
    {
        $this->enqueueExchanges = $amqpTopics;
        $this->enqueueQueues = $amqpQueues;
        $this->amqpBindings = $amqpBindings;
    }

    /**
     * @param AmqpExchange[] $amqpExchanges
     * @param AmqpQueue[] $amqpQueues
     * @param AmqpBinding[] $amqpBindings
     * @return AmqpAdmin
     * @throws MessagingException
     */
    public static function createWith(iterable $amqpExchanges, iterable $amqpQueues, iterable $amqpBindings): self
    {
        $enqueueExchanges = [];
        $enqueueQueues = [];
        $defaultBindings = [];

        foreach ($amqpExchanges as $amqpExchange) {
            $enqueueExchanges[$amqpExchange->getExchangeName()] = $amqpExchange->toEnqueueExchange();
        }
        foreach ($amqpQueues as $amqpQueue) {
            $enqueueQueues[$amqpQueue->getQueueName()] = $amqpQueue->toEnqueueQueue();
            if (! self::hasBinding($amqpBindings, $amqpQueue)) {
                $defaultBindings[] = AmqpBinding::createForDefaultExchange($amqpQueue);
            }
        }

        return new self($enqueueExchanges, $enqueueQueues, array_merge($defaultBindings, $amqpBindings));
    }

    /**
     * @param AmqpBinding[] $amqpBindings
     * @param AmqpQueue $amqpQueue
     * @return bool
     */
    private static function hasBinding(iterable $amqpBindings, AmqpQueue $amqpQueue): bool
    {
        foreach ($amqpBindings as $amqpBinding) {
            if ($amqpBinding->isRelatedToQueueName($amqpQueue->getQueueName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return AmqpAdmin
     */
    public static function createEmpty(): self
    {
        return new self([], [], []);
    }

    /**
     * @param string $exchangeName
     * @param AmqpContext $amqpContext
     * @throws InvalidArgumentException
     */
    public function declareExchangeWithQueuesAndBindings(string $exchangeName, AmqpContext $amqpContext): void
    {
        $this->declareExchange($exchangeName, $amqpContext);

        foreach ($this->amqpBindings as $amqpBinding) {
            if ($amqpBinding->isRelatedToExchangeName($exchangeName)) {
                $this->declareQueueWithBindings($amqpBinding->getQueueName(), $amqpContext);
            }
        }
    }

    /**
     * @param string $exchangeName
     * @param AmqpContext $amqpContext
     * @throws InvalidArgumentException
     */
    private function declareExchange(string $exchangeName, AmqpContext $amqpContext): void
    {
        if ($this->hasExchangeWithName($exchangeName)) {
            $exchange = $this->getExchangeByName($exchangeName);

            $amqpContext->declareTopic($exchange);
        }
    }

    /**
     * @param string $exchangeName
     * @return bool
     */
    public function hasExchangeWithName(string $exchangeName): bool
    {
        return array_key_exists($exchangeName, $this->enqueueExchanges);
    }

    /**
     * @param string $exchangeName
     * @return EnqueueExchange
     * @throws InvalidArgumentException
     */
    public function getExchangeByName(string $exchangeName): EnqueueExchange
    {
        if (! $this->hasExchangeWithName($exchangeName)) {
            throw new InvalidArgumentException("Exchange with name {$exchangeName} was not defined");
        }

        return $this->enqueueExchanges[$exchangeName];
    }

    /**
     * @param string $queueName
     * @param AmqpContext $amqpContext
     * @throws InvalidArgumentException
     */
    public function declareQueueWithBindings(string $queueName, AmqpContext $amqpContext): void
    {
        if (! $this->hasQueueWithName($queueName)) {
            throw new \InvalidArgumentException("Can't declare {$queueName} no information about it");
        }

        $queue = $this->getQueueByName($queueName);
        $amqpContext->declareQueue($queue);

        foreach ($this->amqpBindings as $amqpBinding) {
            if ($amqpBinding->isRelatedToQueueName($queueName) && ! $amqpBinding->isBindToDefaultExchange()) {
                $this->declareExchange($amqpBinding->getExchangeName(), $amqpContext);
                $amqpContext->bind($amqpBinding->toEnqueueBinding());
            }
        }
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function hasQueueWithName(string $queueName): bool
    {
        return array_key_exists($queueName, $this->enqueueQueues);
    }

    /**
     * @param string $queueName
     * @return EnqueueQueue
     * @throws InvalidArgumentException
     */
    public function getQueueByName(string $queueName): EnqueueQueue
    {
        if (! $this->hasQueueWithName($queueName)) {
            throw new InvalidArgumentException("Queue with name {$queueName} was not defined");
        }

        return $this->enqueueQueues[$queueName];
    }
}
