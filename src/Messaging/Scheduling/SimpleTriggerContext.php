<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class SimpleTriggerContext
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SimpleTriggerContext implements TriggerContext
{
    /**
     * SimpleTriggerContext constructor.
     */
    private function __construct(private ?DatePoint $lastScheduledExecutionTime, private ?DatePoint $lastActualExecutionTime)
    {
    }

    /**
     * @return SimpleTriggerContext
     */
    public static function createEmpty(): self
    {
        return new self(null, null);
    }

    /**
     * @return SimpleTriggerContext
     */
    public static function createWith(?DatePoint $lastScheduledExecutionTime, ?DatePoint $lastActualExecutionTime): self
    {
        return new self($lastScheduledExecutionTime, $lastActualExecutionTime);
    }

    public function withLastScheduledExecutionTime(DatePoint $lastScheduledExecutionTime): self
    {
        $this->lastScheduledExecutionTime = $lastScheduledExecutionTime;

        return new self($lastScheduledExecutionTime, $this->lastActualExecutionTime());
    }

    public function withLastActualExecutionTime(DatePoint $lastActualExecutionTime): self
    {
        $this->lastActualExecutionTime = $lastActualExecutionTime;

        return new self($this->lastScheduledExecutionTime, $lastActualExecutionTime);
    }

    /**
     * @inheritDoc
     */
    public function lastScheduledTime(): ?DatePoint
    {
        return $this->lastScheduledExecutionTime;
    }

    /**
     * @inheritDoc
     */
    public function lastActualExecutionTime(): ?DatePoint
    {
        return $this->lastActualExecutionTime;
    }
}
