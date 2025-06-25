<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface Trigger
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface Trigger
{
    public function nextExecutionTime(EcotoneClockInterface $clock, TriggerContext $triggerContext): DatePoint;
}
