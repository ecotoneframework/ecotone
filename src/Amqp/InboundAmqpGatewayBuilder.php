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
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;

/**
 * Class InboundEnqueueGatewayBuilder
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundAmqpGatewayBuilder implements ChannelAdapterConsumerBuilder
{
    private const DEFAULT_RECEIVE_TIMEOUT = 10000;

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
    private $receiveTimeoutInMilliseconds = self::DEFAULT_RECEIVE_TIMEOUT;
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
     * @return string
     */
    public function getEndpointId(): string
    {
        return $this->endpointId;
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
        return [$this->amqpConnectionReferenceName, AmqpAdmin::REFERENCE_NAME];
    }

    /**
     * @inheritDoc
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor)
    {
        // TODO: Implement addBeforeInterceptor() method.
    }

    /**
     * @inheritDoc
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor)
    {
        // TODO: Implement addAfterInterceptor() method.
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        // TODO: Implement addAroundInterceptor() method.
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        // TODO: Implement getInterceptedInterface() method.
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        // TODO: Implement withEndpointAnnotations() method.
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        // TODO: Implement getEndpointAnnotations() method.
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        // TODO: Implement getRequiredInterceptorReferenceNames() method.
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        // TODO: Implement withRequiredInterceptorNames() method.
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, ?PollingMetadata $pollingMetadata): ConsumerLifecycle
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
            $customTransactionReferenceName => new AcknowledgementCallbackTransactionFactory()
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

    public function __toString()
    {
        return "Inbound Amqp Adapter with id " . $this->endpointId;
    }
}