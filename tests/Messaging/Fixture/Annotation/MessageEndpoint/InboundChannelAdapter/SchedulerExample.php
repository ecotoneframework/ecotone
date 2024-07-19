<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Attribute\Scheduled;

/**
 * licence Apache-2.0
 */
class SchedulerExample
{
    #[Scheduled('requestChannel', 'run', ['some'])]
    public function doRun(): array
    {
        return [];
    }
}
