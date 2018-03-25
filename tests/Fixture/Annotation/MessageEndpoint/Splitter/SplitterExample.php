<?php

namespace Fixture\Annotation\MessageEndpoint\Splitter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\SplitterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

/**
 * Class SplitterExample
 * @package Fixture\Annotation\MessageEndpoint\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class SplitterExample
{
    /**
     * @param string $payload
     * @return array
     * @SplitterAnnotation(inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *      @MessageToPayloadParameterAnnotation(parameterName="payload")
     * })
     */
    public function split(string $payload) : array
    {
        return [];
    }
}