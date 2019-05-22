<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpTopic;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class OutboundAmqpGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapter implements MessageHandler
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
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var string|null
     */
    private $routingKeyHeaderName;
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;

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
     * @param HeaderMapper          $headerMapper
     * @param ConversionService     $conversionService
     * @param MediaType             $defaultConversionMediaType
     */
    public function __construct(AmqpConnectionFactory $amqpConnectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, ?string $routingKeyHeaderName, bool $defaultPersistentDelivery, bool $autoDeclare, HeaderMapper $headerMapper, ConversionService $conversionService, MediaType $defaultConversionMediaType)
    {
        $this->amqpConnectionFactory     = $amqpConnectionFactory;
        $this->routingKey                = $routingKey;
        $this->exchangeName              = $exchangeName;
        $this->amqpAdmin                 = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->headerMapper              = $headerMapper;
        $this->autoDeclare               = $autoDeclare;
        $this->routingKeyHeaderName      = $routingKeyHeaderName;
        $this->conversionService = $conversionService;
        $this->defaultConversionMediaType = $defaultConversionMediaType;
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

        $enqueueMessagePayload = $message->getPayload();
        $mediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : null;
        if (!is_string($enqueueMessagePayload)) {
            if (!$message->getHeaders()->hasContentType()) {
                throw new InvalidArgumentException("Can't send message to amqp channel. Payload has incorrect type, that can't be converted: " . TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString());
            }

            if ($this->conversionService->canConvert(
                $message->getHeaders()->getContentType()->hasTypeParameter() ? $message->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($enqueueMessagePayload),
                $message->getHeaders()->getContentType(),
                TypeDescriptor::createStringType(),
                $this->defaultConversionMediaType
            )) {
                $mediaType = $this->defaultConversionMediaType;
                $enqueueMessagePayload = $this->conversionService->convert(
                    $enqueueMessagePayload,
                    $message->getHeaders()->getContentType()->hasTypeParameter() ? $message->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($enqueueMessagePayload),
                    $message->getHeaders()->getContentType(),
                    TypeDescriptor::createStringType(),
                    $this->defaultConversionMediaType
                );
            }else {
                throw new InvalidArgumentException("Can't send message to amqp channel. Payload has incorrect type, that can't be converted: " . TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString());
            }
        }

        $applicationHeaders = $this->headerMapper->mapFromMessageHeaders($message->getHeaders()->headers());
        $messageToSend = new \Interop\Amqp\Impl\AmqpMessage($enqueueMessagePayload, $applicationHeaders, []);


        if ($this->routingKeyHeaderName) {
            $routingKey = $message->getHeaders()->containsKey($this->routingKeyHeaderName) ? $message->getHeaders()->get($this->routingKeyHeaderName) : $this->routingKey;
        }else {
            $routingKey = $this->routingKey;
        }

        if ($mediaType) {
            $messageToSend->setContentType($mediaType->toString());
        }

        if (!is_null($routingKey) && $routingKey !== "") {
            $messageToSend->setRoutingKey($routingKey);
        }
        $messageToSend->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);

        $context->createProducer()->send(new \Interop\Amqp\Impl\AmqpTopic($this->exchangeName), $messageToSend);
        $context->close();
    }
}