<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter;

use SimplyCodedSoftware\Messaging\Annotation\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

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
     *     inputChannelName="requestChannel",
     *     requiredInterceptorNames={"some"}
     * )
     */
    public function doRun() : array
    {
        return [];
    }
}