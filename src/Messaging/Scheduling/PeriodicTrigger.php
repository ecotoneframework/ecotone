<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class PeriodicTrigger
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PeriodicTrigger implements Trigger
{
    /**
     * PeriodicTrigger constructor.
     */
    private function __construct(private Duration $fixedRate, private Duration $initialDelay)
    {
    }

    /**
     * @param int $fixedRateInMilliseconds
     * @param int $initialDelayInMilliseconds
     * @return PeriodicTrigger
     */
    public static function create(int $fixedRateInMilliseconds, int $initialDelayInMilliseconds): self
    {
        return new self(Duration::milliseconds($fixedRateInMilliseconds), Duration::milliseconds($initialDelayInMilliseconds));
    }

    /**
     * @inheritDoc
     */
    public function nextExecutionTime(EcotoneClockInterface $clock, TriggerContext $triggerContext): DatePoint
    {
        if ($this->isFirstSchedule($triggerContext)) {
            return $clock->now()->add($this->initialDelay);
        }

        if ($this->isPlannedAndNeverExecuted($triggerContext) || $this->isPlannedTimeAfterExecution($triggerContext)) {
            return $triggerContext->lastScheduledTime();
        }

        return $triggerContext->lastScheduledTime()
                ? $triggerContext->lastScheduledTime()->add($this->fixedRate)
                : $triggerContext->lastActualExecutionTime()->add($this->fixedRate);
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
        return $triggerContext->lastScheduledTime() && $triggerContext->lastScheduledTime() > $triggerContext->lastActualExecutionTime();
    }
}
