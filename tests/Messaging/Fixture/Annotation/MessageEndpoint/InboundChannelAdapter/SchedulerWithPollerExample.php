<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Api\Attribute\Poller;
use Ecotone\Api\Attribute\Scheduled;

/**
 * licence Apache-2.0
 */
class SchedulerWithPollerExample
{
    #[Scheduled('requestChannel', 'run')]
    #[Poller(
        cron: '*****',
        errorChannelName: 'errorChannel',
        initialDelayInMilliseconds: 100,
        memoryLimitInMegabytes: 100,
        handledMessageLimit: 10,
        executionTimeLimitInMilliseconds: 100
    )]
    public function doRun(): array
    {
        return [];
    }
}
