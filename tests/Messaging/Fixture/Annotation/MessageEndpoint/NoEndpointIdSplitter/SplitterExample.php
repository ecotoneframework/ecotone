<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Splitter;

/**
 * Class SplitterExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class SplitterExample
{
    /**
     * @param string $payload
     * @return array
     * @Splitter(inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *      @Payload(parameterName="payload")
     * })
     */
    public function split(string $payload) : array
    {
        return [];
    }
}