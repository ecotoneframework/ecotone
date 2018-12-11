<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;
use SimplyCodedSoftware\Messaging\Scheduling\TaskScheduler;
use SimplyCodedSoftware\Messaging\Scheduling\Trigger;

/**
 * Class InboundChannelAdapter
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapter implements ConsumerLifecycle
{
    /**
     * @var boolean
     */
    private $isRunning = false;
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
     * @inheritDoc
     */
    public function start(): void
    {
        $this->isRunning = true;

        while ($this->isRunning) {
            $this->taskScheduler->schedule($this->taskExecutor, $this->trigger);
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->isRunning = false;
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