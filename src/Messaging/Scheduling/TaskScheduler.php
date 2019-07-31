<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface TaskScheduler
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TaskScheduler
{
    /**
     * @param TaskExecutor $taskExecutor
     * @param Trigger $trigger
     */
    public function schedule(TaskExecutor $taskExecutor, Trigger $trigger): void;
}