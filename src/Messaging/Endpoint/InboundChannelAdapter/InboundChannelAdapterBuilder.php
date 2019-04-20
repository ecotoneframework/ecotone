<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
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
    private $requestChannelName;
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
     * InboundChannelAdapterBuilder constructor.
     * @param string $inputChannelName
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $inputChannelName, string $referenceName, string $methodName)
    {
        $this->requestChannelName = $inputChannelName;
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
    }

    /**
     * @param string $inputChannelName
     * @param string $referenceName
     * @param string $methodName
     * @return InboundChannelAdapterBuilder
     */
    public static function create(string $inputChannelName, string $referenceName, string $methodName) : self
    {
        return new self($inputChannelName, $referenceName, $methodName);
    }

    /**
     * @param TaskExecutor $taskExecutor
     * @return InboundChannelAdapterBuilder
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
     * @param string $inputChannelName
     * @return InboundChannelAdapterBuilder
     */
    public function withInputChannelName(string $inputChannelName) : self
    {
        $this->requestChannelName = $inputChannelName;

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
    public function getRequestChannelName(): string
    {
        return $this->requestChannelName;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, ?PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($this->endpointId, "Endpoint Id for inbound channel adapter can't be empty");

        $taskExecutor = $this->taskExecutor;
        $forwardChannel = DirectChannel::create();
        /** @var TaskExecutor $forwardGateway */
        $forwardGateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), TaskExecutor::class, "execute", "forwardChannel")
                            ->withTransactionFactories($this->transactionFactoriesReferenceNames)
                            ->withErrorChannel($this->errorChannel)
                            ->build(
                                $referenceSearchService,
                                InMemoryChannelResolver::createWithChannelResolver($channelResolver, [
                                    "forwardChannel" => $forwardChannel
                                ])
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
}