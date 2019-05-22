<?php


namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Endpoint\NullEntrypointGateway;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class AmqpBackedMessageChannelBuilder
 * @package SimplyCodedSoftware\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackedMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds = AmqpInboundChannelAdapterBuilder::DEFAULT_RECEIVE_TIMEOUT;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;

    /**
     * AmqpBackedMessageChannelBuilder constructor.
     *
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     */
    private function __construct(string $channelName, string $amqpConnectionReferenceName)
    {
        $this->channelName                 = $channelName;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
    }

    /**
     * How long it should try to receive message
     *
     * @param int $timeoutInMilliseconds
     *
     * @return AmqpBackedMessageChannelBuilder
     */
    public function withReceiveTimeout(int $timeoutInMilliseconds) : self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    /**
     * @param string $mediaType
     *
     * @return AmqpBackedMessageChannelBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function withDefaultConversionMediaType(string $mediaType) : self
    {
        $this->defaultConversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);

        $amqpOutboundChannelAdapter = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($this->amqpConnectionReferenceName)
                                        ->withAutoDeclareOnSend(true)
                                        ->withDefaultRoutingKey($this->channelName)
                                        ->withHeaderMapper("*")
                                        ->withDefaultPersistentMode(true);

        if ($this->defaultConversionMediaType) {
            /** @var AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter */
            $amqpOutboundChannelAdapter = $amqpOutboundChannelAdapter
                                            ->withDefaultConversionMediaType($this->defaultConversionMediaType)
                                            ->build(InMemoryChannelResolver::createEmpty(), $referenceSearchService);
        }

        $inboundChannelAdapter = new AmqpInboundChannelAdapter(
            $amqpConnectionFactory,
            NullEntrypointGateway::create(),
            $amqpAdmin,
            true,
            $this->channelName,
            AmqpInboundChannelAdapterBuilder::DEFAULT_RECEIVE_TIMEOUT,
            AmqpAcknowledgementCallback::AUTO_ACK,
            DefaultHeaderMapper::createAllHeadersMapping()
        );

        return new AmqpBackendMessageChannel($inboundChannelAdapter, $amqpOutboundChannelAdapter);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [$this->amqpConnectionReferenceName];
    }
}