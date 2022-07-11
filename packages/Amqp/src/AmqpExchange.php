<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Interop\Amqp\AmqpTopic as EnqueueExchange;

/**
 * Class AmqpExchange
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpExchange
{
    private const DEFAULT_DURABILITY = true;

    /**
     * @var EnqueueExchange
     */
    private $enqueueExchange;
    /**
     * @var bool
     */
    private $withDurability = self::DEFAULT_DURABILITY;

    /**
     * AmqpExchange constructor.
     * @param string $exchangeName
     * @param string $exchangeType
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(string $exchangeName, string $exchangeType)
    {
        $this->enqueueExchange = new \Interop\Amqp\Impl\AmqpTopic($exchangeName);
        $this->enqueueExchange->setType($exchangeType);
    }

    public function isHeadersExchange(): bool
    {
        return $this->enqueueExchange->getType() === EnqueueExchange::TYPE_HEADERS;
    }

    /**
     * exchanges survive broker restart
     *
     * @param bool $isDurable
     * @return self
     */
    public function withDurability(bool $isDurable): self
    {
        $this->withDurability = $isDurable;

        return $this;
    }

    /**
     * exchange is deleted when last queue is unbound from it
     *
     * @return AmqpExchange
     */
    public function withAutoDeletion(): self
    {
        $this->enqueueExchange->addFlag(EnqueueExchange::FLAG_AUTODELETE);

        return $this;
    }

    /**
     * optional, used by plugins and broker-specific features
     *
     * @param string $name
     * @param $value
     * @return AmqpExchange
     */
    public function withArgument(string $name, $value): self
    {
        $this->enqueueExchange->setArgument($name, $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->enqueueExchange->getTopicName();
    }

    /**
     * @return EnqueueExchange
     */
    public function toEnqueueExchange(): EnqueueExchange
    {
        $amqpTopic = clone $this->enqueueExchange;

        if ($this->withDurability) {
            $amqpTopic->addFlag(EnqueueExchange::FLAG_DURABLE);
        }

        return $amqpTopic;
    }

    /**
     * @param string $exchangeName
     * @return AmqpExchange
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createDirectExchange(string $exchangeName): self
    {
        return new self($exchangeName, EnqueueExchange::TYPE_DIRECT);
    }

    /**
     * @param string $exchangeName
     * @return AmqpExchange
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFanoutExchange(string $exchangeName): self
    {
        return new self($exchangeName, EnqueueExchange::TYPE_FANOUT);
    }

    /**
     * @param string $exchangeName
     * @return AmqpExchange
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createTopicExchange(string $exchangeName): self
    {
        return new self($exchangeName, EnqueueExchange::TYPE_TOPIC);
    }

    /**
     * @param string $exchangeName
     * @return AmqpExchange
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createHeadersExchange(string $exchangeName): self
    {
        return new self($exchangeName, EnqueueExchange::TYPE_HEADERS);
    }
}
