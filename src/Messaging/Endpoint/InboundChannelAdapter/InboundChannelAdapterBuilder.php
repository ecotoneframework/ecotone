<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\EntrypointGateway;
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
use SimplyCodedSoftware\Messaging\Scheduling\CronTrigger;
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
    private $gatewayExecutor;
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
    private $endpointId;
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var object
     */
    private $directObject;

    /**
     * InboundChannelAdapterBuilder constructor.
     * @param string $requestChannelName
     * @param string $referenceName
     * @param string $methodName
     * @throws \Exception
     */
    private function __construct(string $requestChannelName, string $referenceName, string $methodName)
    {
        $this->gatewayExecutor = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EntrypointGateway::class, "execute", $requestChannelName);
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
     * @param string $requestChannelName
     * @param $objectToInvoke
     * @param string $methodName
     * @return InboundChannelAdapterBuilder
     * @throws \Exception
     */
    public static function createWithDirectObject(string $requestChannelName, $objectToInvoke, string $methodName) : self
    {
        $self = new self($requestChannelName, "", $methodName);
        $self->directObject = $objectToInvoke;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return array_merge([$this->referenceName], $this->gatewayExecutor->getRequiredReferences());
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
     * @inheritDoc
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->gatewayExecutor->addBeforeInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->gatewayExecutor->addAfterInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->gatewayExecutor->addAroundInterceptor($aroundInterceptorReference);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->directObject
                ? $interfaceToCallRegistry->getFor($this->directObject, $this->methodName)
                : $interfaceToCallRegistry->getForReferenceName($this->referenceName, $this->methodName);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->gatewayExecutor->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->gatewayExecutor->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->gatewayExecutor->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        $this->gatewayExecutor->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($this->endpointId, "Endpoint Id for inbound channel adapter can't be empty");

        return
            InterceptedConsumer::createWith(
                $this,
                $pollingMetadata,
                function() use ($channelResolver, $referenceSearchService, $pollingMetadata) {
                    $referenceService = $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
                    $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($referenceService, $this->methodName);

                    if (!$interfaceToCall->hasNoParameters()) {
                        throw InvalidArgumentException::create("{$interfaceToCall} for InboundChannelAdapter should not have any parameters");
                    }

                    if ($interfaceToCall->hasReturnTypeVoid()) {
                        throw InvalidArgumentException::create("{$interfaceToCall} for InboundChannelAdapter should not be void");
                    }

                    $gateway = $this->gatewayExecutor
                        ->build($referenceSearchService, $channelResolver);

                    $taskExecutor = new InboundChannelTaskExecutor(
                        $gateway,
                        $referenceService,
                        $this->methodName
                    );

                    return new InboundChannelAdapter(
                        $this->endpointId,
                        SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
                        $pollingMetadata->getCron()
                            ? CronTrigger::createWith($pollingMetadata->getCron())
                            : PeriodicTrigger::create($pollingMetadata->getFixedRateInMilliseconds(), $pollingMetadata->getInitialDelayInMilliseconds()),
                        $taskExecutor
                    );
                }
            );
    }
}