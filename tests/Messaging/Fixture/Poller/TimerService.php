<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Poller;

/**
 * licence Apache-2.0
 */
final class TimerService
{
    public function getFixedRate(): int
    {
        return 500; // 500 ms
    }

    public function getCronSchedule(): string
    {
        return '* * * * *'; // Every minute
    }
}
