<?php
declare(strict_types=1);

namespace Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Splitter;

/**
 * Class SplitterExample
 * @package Fixture\Annotation\MessageEndpoint\Splitter
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