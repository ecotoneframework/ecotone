<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class SyncTaskScheduler
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SyncTaskScheduler implements TaskScheduler
{
    private \Ecotone\Messaging\Scheduling\Clock $clock;
    private \Ecotone\Messaging\Scheduling\TriggerContext $triggerContext;

    /**
     * SyncTaskScheduler constructor.
     * @param Clock $clock
     * @param TriggerContext $triggerContext
     */
    private function __construct(Clock $clock, TriggerContext $triggerContext)
    {
        $this->clock = $clock;
        $this->triggerContext = $triggerContext;
    }

    /**
     * @param Clock $clock
     * @return SyncTaskScheduler
     */
    public static function createWithEmptyTriggerContext(Clock $clock) : self
    {
        return new self($clock, SimpleTriggerContext::createEmpty());
    }

    /**
     * @param Clock $clock
     * @param SimpleTriggerContext $triggerContext
     * @return SyncTaskScheduler
     */
    public static function createWith(Clock $clock, SimpleTriggerContext $triggerContext) : self
    {
        return new self($clock, $triggerContext);
    }

    /**
     * @inheritDoc
     */
    public function schedule(TaskExecutor $taskExecutor, Trigger $trigger): void
    {
        $nextExecution = $trigger->nextExecutionTime($this->clock, $this->triggerContext);
        $this->triggerContext = $this->triggerContext->withLastScheduledExecutionTime($nextExecution);

        if (!$this->isScheduleAndNextEqual() && $this->isItTimeForNextExecution()) {
            $this->triggerContext = $this->triggerContext->withLastActualExecutionTime($this->triggerContext->lastScheduledTime());
            $taskExecutor->execute();
        }else {
            usleep($this->howMuchMicrosecondsTimeToWait());
        }
    }

    /**
     * @return bool
     */
    private function isScheduleAndNextEqual(): bool
    {
        if (!$this->triggerContext->lastScheduledTime() || !$this->triggerContext->lastActualExecutionTime()) {
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
    private function howMuchMicrosecondsTimeToWait() : int
    {
        $toWait = $this->triggerContext->lastScheduledTime() - $this->clock->unixTimeInMilliseconds();

        return $toWait < 0 ? 0 : $toWait * 1000;
    }
}