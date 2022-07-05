<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

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