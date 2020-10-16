<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class PeriodicTrigger
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PeriodicTrigger implements Trigger
{
    private int $fixedRateInMilliseconds;
    private int $initialDelayInMilliseconds;

    /**
     * PeriodicTrigger constructor.
     * @param int $fixedRateInMilliseconds
     * @param int $initialDelayInMilliseconds
     */
    private function __construct(int $fixedRateInMilliseconds, int $initialDelayInMilliseconds)
    {
        $this->fixedRateInMilliseconds = $fixedRateInMilliseconds;
        $this->initialDelayInMilliseconds = $initialDelayInMilliseconds;
    }

    /**
     * @param int $fixedRateInMilliseconds
     * @param int $initialDelayInMilliseconds
     * @return PeriodicTrigger
     */
    public static function create(int $fixedRateInMilliseconds, int $initialDelayInMilliseconds) : self
    {
        return new self($fixedRateInMilliseconds, $initialDelayInMilliseconds);
    }

    /**
     * @inheritDoc
     */
    public function nextExecutionTime(Clock $clock, TriggerContext $triggerContext): int
    {
        if ($this->isFirstSchedule($triggerContext)) {
            if ($this->initialDelayInMilliseconds) {
                return $clock->unixTimeInMilliseconds() + $this->initialDelayInMilliseconds;
            }

            return $clock->unixTimeInMilliseconds();
        }

        if ($this->isPlannedAndNeverExecuted($triggerContext) || $this->isPlannedTimeAfterExecution($triggerContext)) {
            return $triggerContext->lastScheduledTime();
        }

        return $triggerContext->lastScheduledTime()
                ? $triggerContext->lastScheduledTime() + $this->fixedRateInMilliseconds
                : $triggerContext->lastActualExecutionTime() + $this->fixedRateInMilliseconds;
    }

    /**
     * @param TriggerContext $triggerContext
     * @return bool
     */
    private function isFirstSchedule(TriggerContext $triggerContext): bool
    {
        return is_null($triggerContext->lastScheduledTime()) && is_null($triggerContext->lastActualExecutionTime());
    }

    /**
     * @param TriggerContext $triggerContext
     * @return bool
     */
    private function isPlannedAndNeverExecuted(TriggerContext $triggerContext): bool
    {
        return $triggerContext->lastScheduledTime() && is_null($triggerContext->lastActualExecutionTime());
    }

    /**
     * @param TriggerContext $triggerContext
     * @return bool
     */
    private function isPlannedTimeAfterExecution(TriggerContext $triggerContext): bool
    {
        return $triggerContext->lastScheduledTime() > $triggerContext->lastActualExecutionTime();
    }
}