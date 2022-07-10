<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Scheduling\TaskScheduler;
use Ecotone\Messaging\Scheduling\Trigger;

/**
 * Class InboundChannelAdapter
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapter implements ConsumerLifecycle
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