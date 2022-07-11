<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\Impl\AmqpTopic;

/**
 * Class OutboundAmqpGateway
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapter implements MessageHandler
{
    /**
     * @var CachedConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var string|null
     */
    private $routingKey;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var bool
     */
    private $defaultPersistentDelivery;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var string|null
     */
    private $routingKeyFromHeaderName;
    /**
     * @var string|null
     */
    private $exchangeFromHeaderName;
    /**
     * @var OutboundMessageConverter
     */
    private $outboundMessageConverter;
    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(CachedConnectionFactory $connectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, ?string $routingKeyFromHeaderName, ?string $exchangeFromHeaderName, bool $defaultPersistentDelivery, bool $autoDeclare, OutboundMessageConverter $outboundMessageConverter)
    {
        $this->connectionFactory         = $connectionFactory;
        $this->routingKey                = $routingKey;
        $this->exchangeName              = $exchangeName;
        $this->amqpAdmin                 = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->autoDeclare               = $autoDeclare;
        $this->routingKeyFromHeaderName  = $routingKeyFromHeaderName;
        $this->exchangeFromHeaderName    = $exchangeFromHeaderName;
        $this->outboundMessageConverter  = $outboundMessageConverter;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $exchangeName = $this->exchangeName;
        if ($this->exchangeFromHeaderName) {
            $exchangeName = $message->getHeaders()->containsKey($this->exchangeFromHeaderName) ? $message->getHeaders()->get($this->exchangeFromHeaderName) : $this->exchangeName;
        }
        if (! $this->initialized && $this->autoDeclare) {
            $this->amqpAdmin->declareExchangeWithQueuesAndBindings($exchangeName, $this->connectionFactory->createContext());
            $this->initialized = true;
        }

        $outboundMessage = $this->outboundMessageConverter->prepare($message);
        $messageToSend   = new \Interop\Amqp\Impl\AmqpMessage($outboundMessage->getPayload(), $outboundMessage->getHeaders(), []);

        if ($this->routingKeyFromHeaderName) {
            $routingKey = $message->getHeaders()->containsKey($this->routingKeyFromHeaderName) ? $message->getHeaders()->get($this->routingKeyFromHeaderName) : $this->routingKey;
        } else {
            $routingKey = $this->routingKey;
        }

        if ($outboundMessage->getContentType()) {
            $messageToSend->setContentType($outboundMessage->getContentType());
        }

        if (! is_null($routingKey) && $routingKey !== '') {
            $messageToSend->setRoutingKey($routingKey);
        }

        $messageToSend
            ->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);

        $this->connectionFactory->getProducer()
            ->setTimeToLive($outboundMessage->getTimeToLive())
            ->setDelayStrategy(new HeadersExchangeDelayStrategy())
            ->setDeliveryDelay($outboundMessage->getDeliveryDelay())
//            this allow for having queue per delay instead of queue per delay + exchangeName
            ->send(new AmqpTopic($exchangeName), $messageToSend);
    }
}
