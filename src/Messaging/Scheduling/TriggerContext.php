<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface TriggerContext
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface TriggerContext
{
    public function lastScheduledTime(): ?DatePoint;

    public function lastActualExecutionTime(): ?DatePoint;
}
