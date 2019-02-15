<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter\MessageDrivenConsumer;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\Transaction\AcknowledgementCallbackTransaction;
use SimplyCodedSoftware\Messaging\Transaction\AcknowledgementCallbackTransactionFactory;

/**
 * Class InboundEnqueueGatewayBuilder
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundAmqpGatewayBuilder implements ChannelAdapterConsumerBuilder
{
    private const NO_RECEIVE_TIMEOUT = 0;

    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var AmqpBinding[]
     */
    private $bindings;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds = self::NO_RECEIVE_TIMEOUT;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var string
     */
    private $acknowledgeMode = AmqpAcknowledgementCallback::AUTO_ACK;
    /**
     * @var string[]
     */
    private $transactionReferenceNames = [];

    /**
     * InboundAmqpEnqueueGatewayBuilder constructor.
     * @param string $endpointId
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     */
    private function __construct(string $endpointId, string $queueName, string $requestChannelName, string $amqpConnectionReferenceName)
    {
        $this->endpointId = $endpointId;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->requestChannelName = $requestChannelName;
        $this->headerMapper = DefaultHeaderMapper::createNoMapping();
    }

    /**
     * @param string $endpointId
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     * @return InboundAmqpGatewayBuilder
     */
    public static function createWith(string $endpointId, string $queueName, string $requestChannelName, string $amqpConnectionReferenceName) : self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @param string $exchangeName
     * @param string $routingKey
     * @return InboundAmqpGatewayBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function withBinding(string $exchangeName, string $routingKey) : self
    {
        $this->bindings[] = AmqpBinding::createFromNames($exchangeName, $this->queueName, $routingKey);

        return $this;
    }

    /**
     * @param string $headerMapper
     * @return InboundAmqpGatewayBuilder
     */
    public function withHeaderMapper(string $headerMapper) : self
    {
        $this->headerMapper = DefaultHeaderMapper::createWith(explode(",", $headerMapper), []);

        return $this;
    }

    /**
     * How long it should try to receive message before ending consumer life cycle
     * Can be used in testing scenarios, when synchronous call is needed
     *
     * @param int $timeoutInMilliseconds
     * @return InboundAmqpGatewayBuilder
     */
    public function withReceiveTimeout(int $timeoutInMilliseconds) : self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): ConsumerLifecycle
    {
        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);

        $customConverterReferenceName = Uuid::uuid4()->toString();
        $customTransactionReferenceName = Uuid::uuid4()->toString();
        /** @var InboundAmqpGateway $gateway */
        $referenceSearchService1 = InMemoryReferenceSearchService::createWithReferenceService($referenceSearchService, [
            $customConverterReferenceName => AmqpMessageConverter::createWithMapper($amqpConnectionFactory, $this->headerMapper, $this->acknowledgeMode),
            $customTransactionReferenceName => AcknowledgementCallbackTransactionFactory::createWith(AmqpHeader::HEADER_ACKNOWLEDGE)
        ]);

        $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), InboundAmqpGateway::class, "execute", $this->requestChannelName)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("amqpMessage"),
                            GatewayHeaderBuilder::create("consumer", AmqpHeader::HEADER_CONSUMER),
                            GatewayHeaderBuilder::create("amqpMessage", AmqpHeader::HEADER_AMQP_MESSAGE)
                        ])
                        ->withTransactionFactories(array_merge($this->transactionReferenceNames, [$customTransactionReferenceName]))
                        ->withMessageConverters([$customConverterReferenceName])
                        ->build($referenceSearchService1, $channelResolver);

        return
            MessageDrivenConsumer::create(
                $this->endpointId,
                new InboundAmqpEnqueueGateway(
                    $amqpConnectionFactory,
                    $gateway,
                    $amqpAdmin,
                    true,
                    $this->queueName,
                    $this->receiveTimeoutInMilliseconds,
                    $this->acknowledgeMode
                )
            );
    }
}