<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpTopic;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class OutboundAmqpGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OutboundAmqpGateway implements MessageHandler
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
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
     * @var MessageConverter
     */
    private $messageConverter;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var string|null
     */
    private $routingKeyHeaderName;

    /**
     * OutboundAmqpGateway constructor.
     *
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param AmqpAdmin             $amqpAdmin
     * @param string                $exchangeName
     * @param string|null           $routingKey
     * @param string|null           $routingKeyHeaderName
     * @param bool                  $defaultPersistentDelivery
     * @param bool                  $autoDeclare
     * @param MessageConverter      $messageConverter
     */
    public function __construct(AmqpConnectionFactory $amqpConnectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, ?string $routingKeyHeaderName, bool $defaultPersistentDelivery, bool $autoDeclare, MessageConverter $messageConverter)
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->routingKey = $routingKey;
        $this->exchangeName = $exchangeName;
        $this->amqpAdmin = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->messageConverter = $messageConverter;
        $this->autoDeclare = $autoDeclare;
        $this->routingKeyHeaderName = $routingKeyHeaderName;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        if ($this->autoDeclare) {
            $this->amqpAdmin->declareExchangeWithQueuesAndBindings($this->exchangeName, $context);
        }

        /** @var AmqpMessage $messageToSend */
        $messageToSend = $this->messageConverter->fromMessage($message, TypeDescriptor::create(AmqpMessage::class));

        if ($this->routingKeyHeaderName) {
            $routingKey = $message->getHeaders()->containsKey($this->routingKeyHeaderName) ? $message->getHeaders()->get($this->routingKeyHeaderName) : $this->routingKey;
        }else {
            $routingKey = $this->routingKey;
        }

        if (!is_null($routingKey) && $routingKey !== "") {
            $messageToSend->setRoutingKey($routingKey);
        }
        $messageToSend->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);

        $context->createProducer()->send(new \Interop\Amqp\Impl\AmqpTopic($this->exchangeName), $messageToSend);
        $context->close();
    }
}