<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use Ecotone\Messaging\Annotation\InboundChannelAdapter;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Class InboundChannelAdapterExample
 * @package Fixture\Annotation\MessageEndpoint\InboundChannelAdapter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class InboundChannelAdapterExample
{
    /**
     * @return array
     * @InboundChannelAdapter(
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