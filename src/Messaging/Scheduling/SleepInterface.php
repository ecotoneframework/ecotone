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
 * @TODO Ecotone 2.0 Think about testing delayed messages based on Clock and Sleep
 */
interface SleepInterface
{
    public function sleep(Duration $duration): void;
}
