<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Attribute\Poller;
use Ecotone\Messaging\Attribute\Scheduled;

class SchedulerWithPollerExample
{
    #[Scheduled("requestChannel", "run")]
    #[Poller(
        cron: "*****", errorChannelName: "errorChannel", initialDelayInMilliseconds: 100, memoryLimitInMegabytes: 100, handledMessageLimit: 10, executionTimeLimitInMilliseconds: 100
    )]
    public function doRun() : array
    {
        return [];
    }
}