<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Scheduling\Trigger;

/**
 * Class InboundChannelAdapter
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ScheduledTaskConsumer implements ConsumerLifecycle
{
    public function __construct(private SyncTaskScheduler $taskScheduler, private Trigger $trigger, private PollToGatewayTaskExecutor $taskExecutor)
    {
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
}
