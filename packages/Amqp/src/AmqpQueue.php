<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Interop\Amqp\Impl\AmqpQueue as EnqueueQueue;

/**
 * Class AmqpQueue
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpQueue
{
    private const DEFAULT_DURABILITY = true;

    private EnqueueQueue $enqueueQueue;
    private bool $withDurability = self::DEFAULT_DURABILITY;
    private ?string $withDeadLetterExchange = null;
    private ?string $withDeadLetterRoutingKey = null;

    /**
     * AmqpQueue constructor.
     * @param string $queueName
     */
    private function __construct(string $queueName)
    {
        $this->enqueueQueue = new EnqueueQueue($queueName);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->enqueueQueue->getQueueName();
    }

    /**
     * @param string $queueName
     * @return AmqpQueue
     */
    public static function createWith(string $queueName): self
    {
        return new self($queueName);
    }

    /**
     * @return EnqueueQueue
     */
    public function toEnqueueQueue(): EnqueueQueue
    {
        $amqpQueue = clone $this->enqueueQueue;

        if ($this->withDurability) {
            $amqpQueue->addFlag(EnqueueQueue::FLAG_DURABLE);
        }
        if (! is_null($this->withDeadLetterExchange)) {
            $amqpQueue->setArgument('x-dead-letter-exchange', $this->withDeadLetterExchange);
        }
        if ($this->withDeadLetterRoutingKey) {
            $amqpQueue->setArgument('x-dead-letter-routing-key', $this->withDeadLetterRoutingKey);
        }

        return $amqpQueue;
    }

    public function withDeadLetterExchangeTarget(AmqpExchange $amqpExchange, ?string $routingKey = null): self
    {
        $this->withDeadLetterExchange = $amqpExchange->getExchangeName();
        $this->withDeadLetterRoutingKey = $routingKey;

        return $this;
    }

    public function withDeadLetterForDefaultExchange(AmqpQueue $amqpQueue): self
    {
        $this->withDeadLetterExchange = '';
        $this->withDeadLetterRoutingKey = $amqpQueue->getQueueName();

        return $this;
    }

    /**
     * the queue will survive a broker restart
     *
     * @param bool $isDurable
     * @return AmqpQueue
     */
    public function withDurability(bool $isDurable): self
    {
        $this->withDurability = $isDurable;

        return $this;
    }

    /**
     * used by only one connection and the queue will be deleted when that connection closes
     *
     * @return AmqpQueue
     */
    public function withExclusivity(): self
    {
        $this->enqueueQueue->addFlag(EnqueueQueue::FLAG_EXCLUSIVE);

        return $this;
    }

    /**
     * queue that has had at least one consumer is deleted when last consumer unsubscribes
     *
     * @return AmqpQueue
     */
    public function withAutoDeletion(): self
    {
        $this->enqueueQueue->addFlag(EnqueueQueue::FLAG_AUTODELETE);

        return $this;
    }

    /**
     * optional, used by plugins and broker-specific features such as message TTL, queue length limit, etc
     *
     * @param string $name
     * @param $value
     * @return AmqpQueue
     */
    public function withArgument(string $name, $value): self
    {
        $this->enqueueQueue->setArgument($name, $value);

        return $this;
    }
}
