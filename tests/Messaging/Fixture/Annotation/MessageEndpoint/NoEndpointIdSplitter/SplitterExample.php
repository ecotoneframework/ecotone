<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Splitter;

class SplitterExample
{
    /**
     * @Splitter(inputChannelName="inputChannel", outputChannelName="outputChannel")
     */
    public function split(#[Payload] string $payload) : array
    {
        return [];
    }
}