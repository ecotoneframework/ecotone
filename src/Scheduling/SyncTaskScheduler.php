<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Scheduling;

/**
 * Class SyncTaskScheduler
 * @package SimplyCodedSoftware\IntegrationMessaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SyncTaskScheduler implements TaskScheduler
{
    /**
     * @var Clock
     */
    private $clock;
    /**
     * @var SimpleTriggerContext
     */
    private $triggerContext;

    /**
     * SyncTaskScheduler constructor.
     * @param Clock $clock
     * @param TriggerContext $triggerContext
     */
    private function __construct(Clock $clock, TriggerContext $triggerContext)
    {
        $this->clock = $clock;
        $this->triggerContext = SimpleTriggerContext::createEmpty();
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
        return ($this->clock->unixTimeInMilliseconds() > $this->triggerContext->lastScheduledTime()) || ($this->clock->unixTimeInMilliseconds() == $this->triggerContext->lastScheduledTime());
    }
}