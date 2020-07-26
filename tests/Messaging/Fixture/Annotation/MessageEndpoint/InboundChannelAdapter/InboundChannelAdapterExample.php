<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Annotation\Scheduled;
use Ecotone\Messaging\Annotation\MessageEndpoint;

class InboundChannelAdapterExample
{
    /**
     * @return array
     * @Scheduled(
     *     endpointId="run",
     *     requestChannelName="requestChannel",
     *     requiredInterceptorNames={"some"}
     * )
     */
    public function doRun() : array
    {
        return [];
    }
}