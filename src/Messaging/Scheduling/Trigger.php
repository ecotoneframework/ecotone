<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface Trigger
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Trigger
{
    /**
     * @param Clock $clock
     * @param TriggerContext $triggerContext
     * @return int 	Milliseconds since Epoch
     */
    public function nextExecutionTime(Clock $clock, TriggerContext $triggerContext) : int;
}