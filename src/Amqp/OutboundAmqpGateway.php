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
     * OutboundAmqpGateway constructor.
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param AmqpAdmin $amqpAdmin
     * @param string $exchangeName
     * @param string|null $routingKey
     * @param bool $defaultPersistentDelivery
     * @param MessageConverter $messageConverter
     */
    public function __construct(AmqpConnectionFactory $amqpConnectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, bool $defaultPersistentDelivery, MessageConverter $messageConverter)
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->routingKey = $routingKey;
        $this->exchangeName = $exchangeName;
        $this->amqpAdmin = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->messageConverter = $messageConverter;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        $this->amqpAdmin->declareExchangeWithQueuesAndBindings($this->exchangeName, $context);

        /** @var AmqpMessage $messageToSend */
        $messageToSend = $this->messageConverter->fromMessage($message, TypeDescriptor::create(AmqpMessage::class));;
        if (!is_null($this->routingKey) && $this->routingKey !== "") {
            $messageToSend->setRoutingKey($this->routingKey);
        }
        $messageToSend->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);

        $context->createProducer()->send(new \Interop\Amqp\Impl\AmqpTopic($this->exchangeName), $messageToSend);
        $context->close();
    }
}