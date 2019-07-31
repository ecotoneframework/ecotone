<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface TaskExecutor
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TaskExecutor
{
    public function execute() : void;
}