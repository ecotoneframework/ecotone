<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueInboundChannelAdapterBuilder;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Endpoint\TaskExecutorChannelAdapter\TaskExecutorChannelAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Enqueue\AmqpExt\AmqpConnectionFactory;

/**
 * Class InboundEnqueueGatewayBuilder
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapterBuilder extends EnqueueInboundChannelAdapterBuilder
{
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var string
     */
    private $queueName;

    private function __construct(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName, bool $withAckInterceptor)
    {
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->initialize($endpointId, $requestChannelName, $amqpConnectionReferenceName);
        $this->withAckInterceptor = $withAckInterceptor;
    }

    public static function createWith(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName, true);
    }

    public static function createWithoutAck(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName, false);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @inheritDoc
     */
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        return TaskExecutorChannelAdapter::createFrom(
            $this->endpointId,
            $pollingMetadata->setFixedRateInMilliseconds(1),
            $this->createInboundChannelAdapter($channelResolver, $referenceSearchService, $pollingMetadata)
        );
    }

    public function createInboundChannelAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): AmqpInboundChannelAdapter
    {
        if ($pollingMetadata->getExecutionTimeLimitInMilliseconds()) {
            $this->withReceiveTimeout($pollingMetadata->getExecutionTimeLimitInMilliseconds());
        }

        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        $inboundAmqpGateway = $this->buildGatewayFor($referenceSearchService, $channelResolver, $pollingMetadata);

        $headerMapper = DefaultHeaderMapper::createWith($this->headerMapper, [], $conversionService);
        $inboundChannelAdapter = new AmqpInboundChannelAdapter(
            CachedConnectionFactory::createFor(new AmqpConsumerConnectionFactory($amqpConnectionFactory)),
            $inboundAmqpGateway,
            $amqpAdmin,
            true,
            $this->queueName,
            $this->receiveTimeoutInMilliseconds,
            new InboundMessageConverter($this->getEndpointId(), $this->acknowledgeMode, AmqpHeader::HEADER_ACKNOWLEDGE, $headerMapper),
            ! $this->withAckInterceptor
        );
        return $inboundChannelAdapter;
    }
}
