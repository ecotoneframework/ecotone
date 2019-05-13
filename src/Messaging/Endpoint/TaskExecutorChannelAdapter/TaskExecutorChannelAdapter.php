<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Endpoint\TaskExecutorChannelAdapter;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\InterceptedConsumer;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Scheduling\CronTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\EpochBasedClock;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\SyncTaskScheduler;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;
use SimplyCodedSoftware\Messaging\Scheduling\TaskScheduler;
use SimplyCodedSoftware\Messaging\Scheduling\Trigger;

/**
 * Class ChannelAdapter
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TaskExecutorChannelAdapter implements ConsumerLifecycle
{
    /**
     * @var string
     */
    private $consumerName;
    /**
     * @var Trigger
     */
    private $trigger;
    /**
     * @var TaskScheduler
     */
    private $taskScheduler;
    /**
     * @var TaskExecutor
     */
    private $taskExecutor;

    /**
     * InboundChannelAdapter constructor.
     * @param string $consumerName
     * @param TaskScheduler $taskScheduler
     * @param Trigger $trigger
     * @param TaskExecutor $taskExecutor
     */
    public function __construct(string $consumerName, TaskScheduler $taskScheduler, Trigger $trigger, TaskExecutor $taskExecutor)
    {
        $this->consumerName = $consumerName;
        $this->taskScheduler = $taskScheduler;
        $this->trigger = $trigger;
        $this->taskExecutor = $taskExecutor;
    }

    /**
     * @param string $endpointId
     * @param PollingMetadata $pollingMetadata
     * @param TaskExecutor $taskExecutor
     * @return TaskExecutorChannelAdapter
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public static function createFrom(string $endpointId, PollingMetadata $pollingMetadata, TaskExecutor $taskExecutor) : self
    {
        return
            new self(
                $endpointId,
                SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
                $pollingMetadata->getCron()
                    ? CronTrigger::createWith($pollingMetadata->getCron())
                    : PeriodicTrigger::create($pollingMetadata->getFixedRateInMilliseconds(), $pollingMetadata->getInitialDelayInMilliseconds()),
                $taskExecutor
            );
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->taskScheduler->schedule($this->taskExecutor, $this->trigger);
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }
}