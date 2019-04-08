<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class OutboundAmqpGatewayBuilder
 * @package SimplyCodedSoftware\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OutboundAmqpGatewayBuilder implements MessageHandlerBuilder
{
    private const DEFAULT_PERSISTENT_MODE = true;
    const         DEFAULT_AUTO_DECLARE    = false;

    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var string
     */
    private $inputChannelName = "";
    /**
     * @var string
     */
    private $amqpConnectionFactoryReferenceName;
    /**
     * @var string
     */
    private $routingKey;
    /**
     * @var string
     */
    private $routingKeyFromHeader;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var bool
     */
    private $defaultPersistentDelivery = self::DEFAULT_PERSISTENT_MODE;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var bool
     */
    private $autoDeclare = self::DEFAULT_AUTO_DECLARE;

    /**
     * OutboundAmqpGatewayBuilder constructor.
     *
     * @param string $exchangeName
     * @param string $amqpConnectionFactoryReferenceName
     */
    private function __construct(string $exchangeName, string $amqpConnectionFactoryReferenceName)
    {
        $this->amqpConnectionFactoryReferenceName = $amqpConnectionFactoryReferenceName;
        $this->exchangeName                       = $exchangeName;
        $this->headerMapper                       = DefaultHeaderMapper::createNoMapping();
    }

    /**
     * @param string $exchangeName
     * @param string $amqpConnectionFactoryReferenceName
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public static function create(string $exchangeName, string $amqpConnectionFactoryReferenceName): self
    {
        return new self($exchangeName, $amqpConnectionFactoryReferenceName);
    }

    /**
     * @param string $amqpConnectionFactoryReferenceName
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public static function createForDefaultExchange(string $amqpConnectionFactoryReferenceName): self
    {
        return new self("", $amqpConnectionFactoryReferenceName);
    }

    /**
     * @param string $routingKey
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public function withDefaultRoutingKey(string $routingKey): self
    {
        $this->routingKey = $routingKey;

        return $this;
    }

    /**
     * @param string $headerName
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public function withRoutingKeyFromHeader(string $headerName) : self
    {
        $this->routingKeyFromHeader = $headerName;

        return $this;
    }

    /**
     * @param bool $isPersistent
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public function withDefaultPersistentMode(bool $isPersistent): self
    {
        $this->defaultPersistentDelivery = $isPersistent;

        return $this;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = DefaultHeaderMapper::createWith([], explode(",", $headerMapper));

        return $this;
    }

    /**
     * @param bool $toDeclare
     *
     * @return OutboundAmqpGatewayBuilder
     */
    public function withAutoDeclareOnSend(bool $toDeclare): self
    {
        $this->autoDeclare = $toDeclare;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionFactoryReferenceName);

        return new OutboundAmqpGateway(
            $amqpConnectionFactory,
            $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME),
            $this->exchangeName,
            $this->routingKey,
            $this->routingKeyFromHeader,
            $this->defaultPersistentDelivery,
            $this->autoDeclare,
            AmqpMessageConverter::createWithMapper($amqpConnectionFactory, $this->headerMapper, AmqpAcknowledgementCallback::NONE)
        );
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(OutboundAmqpGateway::class, "handle")];
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId)
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}