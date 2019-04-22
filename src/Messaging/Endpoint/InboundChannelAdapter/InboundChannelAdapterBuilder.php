<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\InterceptedConsumer;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\SyncTaskScheduler;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;
use SimplyCodedSoftware\Messaging\Scheduling\Trigger;
use SimplyCodedSoftware\Messaging\Scheduling\EpochBasedClock;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class InboundChannelAdapterBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterBuilder implements ChannelAdapterConsumerBuilder
{
    /**
     * @var GatewayBuilder
     */
    private $gateway;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $errorChannel = "";
    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var Trigger
     */
    private $trigger;
    /**
     * @var TaskExecutor
     */
    private $taskExecutor;
    /**
     * @var string[]
     */
    private $transactionFactoriesReferenceNames = [];
    /**
     * @var string
     */
    private $requestChannelName;

    /**
     * InboundChannelAdapterBuilder constructor.
     * @param string $requestChannelName
     * @param string $referenceName
     * @param string $methodName
     * @throws \Exception
     */
    private function __construct(string $requestChannelName, string $referenceName, string $methodName)
    {
        $this->gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), TaskExecutor::class, "execute", "forwardChannel");
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
        $this->requestChannelName = $requestChannelName;
    }

    /**
     * @param string $requestChannelName
     * @param string $referenceName
     * @param string $methodName
     * @return InboundChannelAdapterBuilder
     * @throws \Exception
     */
    public static function create(string $requestChannelName, string $referenceName, string $methodName) : self
    {
        return new self($requestChannelName, $referenceName, $methodName);
    }

    /**
     * @param TaskExecutor $taskExecutor
     * @return InboundChannelAdapterBuilder
     * @throws \Exception
     */
    public static function createWithTaskExecutor(TaskExecutor $taskExecutor) : self
    {
        $self = new self("", "", "");
        $self->taskExecutor = $taskExecutor;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceName];
    }

    /**
     * @param Trigger $trigger
     * @return InboundChannelAdapterBuilder
     */
    public function withTrigger(Trigger $trigger) : self
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    /**
     * @param string $endpointId
     * @return InboundChannelAdapterBuilder
     */
    public function withEndpointId(string $endpointId) : self
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    /**
     * @param array $transactionFactoriesReferenceNames
     * @return InboundChannelAdapterBuilder
     */
    public function withTransactionFactories(array $transactionFactoriesReferenceNames) : self
    {
        $this->transactionFactoriesReferenceNames = $transactionFactoriesReferenceNames;

        return $this;
    }

    /**
     * @param string $errorChannelName
     * @return InboundChannelAdapterBuilder
     */
    public function withErrorChannel(string $errorChannelName) : self
    {
        $this->errorChannel = $errorChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->gateway->addBeforeInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->gateway->addAfterInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->gateway->addAroundInterceptor($aroundInterceptorReference);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->gateway->getInterceptedInterface($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->gateway->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->gateway->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->gateway->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        $this->gateway->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, ?PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($this->endpointId, "Endpoint Id for inbound channel adapter can't be empty");

        return
            InterceptedConsumer::createWith(
                $this,
                $pollingMetadata,
                function() use ($channelResolver, $referenceSearchService, $pollingMetadata) {
                    $taskExecutor = $this->taskExecutor;
                    $forwardChannel = DirectChannel::create();
                    $channelResolver = InMemoryChannelResolver::createWithChannelResolver(
                        $channelResolver,
                        ["forwardChannel" => $forwardChannel]
                    );

                    /** @var TaskExecutor $forwardGateway */
                    $forwardGateway = $this->gateway
                        ->withErrorChannel($this->errorChannel)
                        ->build(
                            $referenceSearchService,
                            $channelResolver
                        );

                    if (!$taskExecutor) {
                        $referenceService = $referenceSearchService->get($this->referenceName);
                        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($referenceService, $this->methodName);

                        if (!$interfaceToCall->hasNoParameters()) {
                            throw InvalidArgumentException::create("{$interfaceToCall} for InboundChannelAdapter should not have any parameters");
                        }

                        if ($interfaceToCall->hasReturnTypeVoid()) {
                            throw InvalidArgumentException::create("{$interfaceToCall} for InboundChannelAdapter should not be void");
                        }

                        $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), InboundChannelGateway::class, "execute", $this->requestChannelName)
                            ->build($referenceSearchService, $channelResolver);
                        Assert::isTrue(\assert($gateway instanceof InboundChannelGateway), "Internal error, wrong class, expected " . InboundChannelGateway::class);

                        $taskExecutor = new InboundChannelTaskExecutor(
                            $gateway,
                            $referenceService,
                            $this->methodName
                        );
                    }

                    $forwardChannel->subscribe(new TaskExecutorBridge($taskExecutor));
                    $trigger = $this->trigger ? $this->trigger : PeriodicTrigger::create(5, 0);

                    return new InboundChannelAdapter(
                        $this->endpointId,
                        SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
                        $trigger,
                        $forwardGateway
                    );
                }
            );
    }
}