<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Scheduling;

/**
 * Interface TriggerContext
 * @package SimplyCodedSoftware\IntegrationMessaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TriggerContext
{
    /**
     * @return int|null Milliseconds since Epoch
     */
    public function lastScheduledTime() : ?int;

    /**
     * @return int|null Milliseconds since Epoch
     */
    public function lastActualExecutionTime() : ?int;
}