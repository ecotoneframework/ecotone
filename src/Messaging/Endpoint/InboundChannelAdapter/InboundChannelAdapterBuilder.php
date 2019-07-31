<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\InterceptedChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\InterceptedConsumer;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Scheduling\CronTrigger;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Scheduling\Trigger;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class InboundChannelAdapterBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterBuilder extends InterceptedChannelAdapterBuilder
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
        $this->gatewayExecutor = GatewayProxyBuilder::create($referenceName, EntrypointGateway::class, "executeEntrypoint", $requestChannelName);
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
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($this->endpointId, "Endpoint Id for inbound channel adapter can't be empty");

        $referenceService = $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
        /** @var InterfaceToCall $interfaceToCall */
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($referenceService, $this->methodName);

        $registeredAnnotations = $this->getEndpointAnnotations();
        foreach ($interfaceToCall->getMethodAnnotations() as $annotation) {
            if ($this->canBeAddedToRegisteredAnnotations($registeredAnnotations, $annotation)) {
                $registeredAnnotations[] = $annotation;
            }
        }
        foreach ($interfaceToCall->getClassAnnotations() as $annotation) {
            if ($this->canBeAddedToRegisteredAnnotations($registeredAnnotations, $annotation)) {
                $registeredAnnotations[] = $annotation;
            }
        }
        $this->gatewayExecutor->withEndpointAnnotations($registeredAnnotations);

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

    /**
     * @param array $registeredAnnotations
     * @param object $annotation
     * @return bool
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     */
    private function canBeAddedToRegisteredAnnotations(array $registeredAnnotations, object $annotation): bool
    {
        foreach ($registeredAnnotations as $registeredAnnotation) {
            if (TypeDescriptor::createFromVariable($registeredAnnotation)->equals(TypeDescriptor::createFromVariable($annotation))) {
                return false;
            }
        }

        return true;
    }
}