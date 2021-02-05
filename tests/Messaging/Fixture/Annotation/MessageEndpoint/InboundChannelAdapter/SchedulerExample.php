<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\Attribute\MessageEndpoint;

class SchedulerExample
{
    #[Scheduled("requestChannel", "run", ["some"])]
    public function doRun() : array
    {
        return [];
    }
}