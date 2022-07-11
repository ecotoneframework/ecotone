<?php

namespace Ecotone\Amqp;

use Ecotone\Enqueue\EnqueueMessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\AmqpExt\AmqpConnectionFactory;

/**
 * Class AmqpBackedMessageChannelBuilder
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackedMessageChannelBuilder extends EnqueueMessageChannelBuilder
{
    private AmqpInboundChannelAdapterBuilder $inboundChannelAdapter;
    private AmqpOutboundChannelAdapterBuilder $outboundChannelAdapter;

    private function __construct(string $channelName, string $amqpConnectionReferenceName)
    {
        $this->inboundChannelAdapter = AmqpInboundChannelAdapterBuilder::createWithoutAck($channelName, $channelName, null, $amqpConnectionReferenceName);
        $this->outboundChannelAdapter = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($amqpConnectionReferenceName)
            ->withDefaultRoutingKey($channelName)
            ->withAutoDeclareOnSend(true)
            ->withDefaultPersistentMode(true);

        $this->withHeaderMapper('*');
    }

    public function withHeaderMapper(string $mapping): self
    {
        $this->inboundChannelAdapter->withHeaderMapper($mapping);
        $this->outboundChannelAdapter->withHeaderMapper($mapping);

        return $this;
    }

    /**
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     *
     * @return AmqpBackedMessageChannelBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function create(string $channelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class)
    {
        return new self($channelName, $amqpConnectionReferenceName);
    }

    public function withReceiveTimeout(int $timeoutInMilliseconds): self
    {
        $this->inboundChannelAdapter->withReceiveTimeout($timeoutInMilliseconds);

        return $this;
    }

    public function withDefaultTimeToLive(int $timeInMilliseconds): self
    {
        $this->outboundChannelAdapter->withDefaultTimeToLive($timeInMilliseconds);

        return $this;
    }

    public function withDefaultDeliveryDelay(int $timeInMilliseconds): self
    {
        $this->outboundChannelAdapter->withDefaultDeliveryDelay($timeInMilliseconds);

        return $this;
    }

    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->outboundChannelAdapter->withDefaultConversionMediaType($mediaType);

        return $this;
    }

    public function getDefaultConversionMediaType(): ?MediaType
    {
        return $this->outboundChannelAdapter->getDefaultConversionMediaType();
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->inboundChannelAdapter->getQueueName();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return array_merge($this->inboundChannelAdapter->getRequiredReferences(), $this->outboundChannelAdapter->getRequiredReferenceNames());
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return array_merge(
            $this->inboundChannelAdapter->resolveRelatedInterfaces($interfaceToCallRegistry),
            $this->outboundChannelAdapter->resolveRelatedInterfaces($interfaceToCallRegistry)
        );
    }

    public function prepareProviderChannel(ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): MessageChannel
    {
        return new AmqpBackendMessageChannel(
            $this->inboundChannelAdapter->createInboundChannelAdapter(InMemoryChannelResolver::createEmpty(), $referenceSearchService, $pollingMetadata),
            $this->outboundChannelAdapter->build(InMemoryChannelResolver::createEmpty(), $referenceSearchService)
        );
    }
}
