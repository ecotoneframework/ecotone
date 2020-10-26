<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Annotation\Scheduled;
use Ecotone\Messaging\Annotation\MessageEndpoint;

class InboundChannelAdapterExample
{
    #[Scheduled("requestChannel", "run", ["some"])]
    public function doRun() : array
    {
        return [];
    }
}