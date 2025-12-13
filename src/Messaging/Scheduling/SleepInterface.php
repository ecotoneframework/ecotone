<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Interface Sleep
 * @package Ecotone\Messaging\Scheduling
 * @author JB Cagumbay <cagumbay.jb@gmail.com>
 */
/**
 * licence Apache-2.0
 */
interface SleepInterface
{
    public function sleep(Duration $duration): void;
}
