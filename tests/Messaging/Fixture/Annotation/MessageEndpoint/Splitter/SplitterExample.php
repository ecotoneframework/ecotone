<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Splitter;

class SplitterExample
{
    /**
     * @Splitter(endpointId="testId", inputChannelName="inputChannel", outputChannelName="outputChannel", requiredInterceptorNames={"someReference"})
     */
    public function split(#[Payload] string $payload) : array
    {
        return [];
    }
}