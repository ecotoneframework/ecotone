<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Scheduling;

/**
 * Interface TaskExecutor
 * @package SimplyCodedSoftware\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TaskExecutor
{
    public function execute() : void;
}