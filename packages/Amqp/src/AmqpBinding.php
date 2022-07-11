<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\Support\Assert;
use Interop\Amqp\Impl\AmqpBind as EnqueueBinding;

/**
 * Class AmqpBinding
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBinding
{
    /**
     * @var EnqueueBinding
     */
    private $enqueueBinding;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var string
     */
    private $exchangeName;

    private function __construct(AmqpExchange $amqpExchange, AmqpQueue $amqpQueue, ?string $routingKey, array $arguments)
    {
        $this->queueName = $amqpQueue->getQueueName();
        $this->exchangeName = $amqpExchange->getExchangeName();
        $this->enqueueBinding = new EnqueueBinding($amqpExchange->toEnqueueExchange(), $amqpQueue->toEnqueueQueue(), $routingKey, 0, $arguments);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * @return bool
     */
    public function isBindToDefaultExchange(): bool
    {
        return $this->exchangeName === '';
    }

    /**
     * @param string $amqpQueueName
     * @return bool
     */
    public function isRelatedToQueueName(string $amqpQueueName): bool
    {
        return $this->queueName == $amqpQueueName;
    }

    /**
     * @param string $amqpExchangeName
     * @return bool
     */
    public function isRelatedToExchangeName(string $amqpExchangeName): bool
    {
        return $this->exchangeName == $amqpExchangeName;
    }

    /**
     * @return EnqueueBinding
     */
    public function toEnqueueBinding(): EnqueueBinding
    {
        return $this->enqueueBinding;
    }

    /**
     * @param AmqpExchange $amqpExchange
     * @param AmqpQueue $amqpQueue
     * @param string|null $routingKey
     * @return AmqpBinding
     */
    public static function createWith(AmqpExchange $amqpExchange, AmqpQueue $amqpQueue, ?string $routingKey): self
    {
        return new self($amqpExchange, $amqpQueue, $routingKey, []);
    }

    /**
     * @param AmqpQueue $amqpQueue
     * @return AmqpBinding
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createForDefaultExchange(AmqpQueue $amqpQueue): self
    {
        return new self(AmqpExchange::createDirectExchange(''), $amqpQueue, $amqpQueue->getQueueName(), []);
    }

    /**
     * @param string $amqpExchangeName
     * @param string $amqpQueueName
     * @param string|null $routingKey
     * @return AmqpBinding
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFromNames(string $amqpExchangeName, string $amqpQueueName, ?string $routingKey): self
    {
        return new self(AmqpExchange::createDirectExchange($amqpExchangeName), AmqpQueue::createWith($amqpQueueName), $routingKey, []);
    }

    /**
     * @param string $amqpExchangeName
     * @param string $amqpQueueName
     *
     * @return AmqpBinding
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFromNamesWithoutRoutingKey(string $amqpExchangeName, string $amqpQueueName): self
    {
        return new self(AmqpExchange::createDirectExchange($amqpExchangeName), AmqpQueue::createWith($amqpQueueName), null, []);
    }

    public static function createHeadersBinding(AmqpExchange $amqpExchange, AmqpQueue $amqpQueueName, array $bindings): self
    {
        Assert::isTrue($amqpExchange->isHeadersExchange(), "Header binding can only be done on headers exchange {$amqpExchange->getExchangeName()} to {$amqpQueueName->getQueueName()}");

        return new self($amqpExchange, $amqpQueueName, null, $bindings);
    }
}
