<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Splitter;

/**
 * Class SplitterExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class SplitterExample
{
    /**
     * @param string $payload
     * @return array
     * @Splitter(endpointId="testId", inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *      @Payload(parameterName="payload")
     * })
     */
    public function split(string $payload) : array
    {
        return [];
    }
}