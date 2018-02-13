<?php

namespace Fixture\Annotation\MessageEndpoint\Transformer;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\TransformerAnnotation;

/**
 * Class TransformerExample
 * @package Fixture\Annotation\MessageEndpoint\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint(referenceName="")
 */
class TransformerWithMethodParameterExample
{
    /**
     * @param string $message
     *
     * @TransformerAnnotation(inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *     @MessageToPayloadParameterAnnotation(parameterName="message")
     * })
     * @return string
     */
    public function send(string $message) : string
    {
        return "";
    }
}