<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class SyncTaskScheduler
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SyncTaskScheduler implements TaskScheduler
{
    private function __construct(private Clock $clock, private TriggerContext $triggerContext, private PollingMetadata $pollingMetadata)
    {
    }

    public static function createWithEmptyTriggerContext(Clock $clock, PollingMetadata $pollingMetadata): self
    {
        return new self($clock, SimpleTriggerContext::createEmpty(), $pollingMetadata);
    }

    public static function createWith(Clock $clock, SimpleTriggerContext $triggerContext, PollingMetadata $pollingMetadata): self
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
            usleep($this->howMuchMicrosecondsTimeToWait());
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

        return ($this->triggerContext->lastScheduledTime() - $this->triggerContext->lastActualExecutionTime()) == 0;
    }

    /**
     * @return bool
     */
    private function isItTimeForNextExecution(): bool
    {
        return $this->clock->unixTimeInMilliseconds() >= $this->triggerContext->lastScheduledTime();
    }

    /**
     * @return int
     */
    private function howMuchMicrosecondsTimeToWait(): int
    {
        $toWait = $this->triggerContext->lastScheduledTime() - $this->clock->unixTimeInMilliseconds();

        return $toWait < 0 ? 0 : $toWait * 1000;
    }
}
