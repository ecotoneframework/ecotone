<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint\TaskExecutorChannelAdapter;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use Ecotone\Messaging\Endpoint\InterceptedConsumer;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Scheduling\CronTrigger;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Scheduling\TaskScheduler;
use Ecotone\Messaging\Scheduling\Trigger;

/**
 * Class ChannelAdapter
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TaskExecutorChannelAdapter implements ConsumerLifecycle
{
    private string $consumerName;
    private \Ecotone\Messaging\Scheduling\Trigger $trigger;
    private \Ecotone\Messaging\Scheduling\TaskScheduler $taskScheduler;
    private \Ecotone\Messaging\Scheduling\TaskExecutor $taskExecutor;

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
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
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