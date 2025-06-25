<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class SyncTaskScheduler
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SyncTaskScheduler implements TaskScheduler
{
    private function __construct(private EcotoneClockInterface $clock, private TriggerContext $triggerContext, private PollingMetadata $pollingMetadata)
    {
    }

    public static function createWithEmptyTriggerContext(EcotoneClockInterface $clock, PollingMetadata $pollingMetadata): self
    {
        return new self($clock, SimpleTriggerContext::createEmpty(), $pollingMetadata);
    }

    public static function createWith(EcotoneClockInterface $clock, SimpleTriggerContext $triggerContext, PollingMetadata $pollingMetadata): self
    {
        return new self($clock, $triggerContext, $pollingMetadata);
    }

    /**
     * @inheritDoc
     */
    public function schedule(TaskExecutor $taskExecutor, Trigger $trigger): void
    {
        $nextExecution = $trigger->nextExecutionTime($this->clock, $this->triggerContext);
        $this->triggerContext = $this->triggerContext->withLastScheduledExecutionTime($nextExecution);

        if (! $this->isScheduleAndNextEqual() && $this->isItTimeForNextExecution()) {
            $this->triggerContext = $this->triggerContext->withLastActualExecutionTime($this->triggerContext->lastScheduledTime());
            $taskExecutor->execute($this->pollingMetadata);
        } else {
            $this->clock->sleep($this->waitTime());
        }
    }

    /**
     * @return bool
     */
    private function isScheduleAndNextEqual(): bool
    {
        if (! $this->triggerContext->lastScheduledTime() || ! $this->triggerContext->lastActualExecutionTime()) {
            return false;
        }

        return $this->triggerContext->lastScheduledTime() == $this->triggerContext->lastActualExecutionTime();
    }

    /**
     * @return bool
     */
    private function isItTimeForNextExecution(): bool
    {
        return $this->clock->now() >= $this->triggerContext->lastScheduledTime();
    }

    /**
     * @return int
     */
    private function waitTime(): Duration
    {
        return $this->triggerContext->lastScheduledTime()->durationSince($this->clock->now());
    }
}
