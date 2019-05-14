<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Transformer;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Transformer;

/**
 * Class TransformerExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint(referenceName="")
 */
class TransformerWithMethodParameterExample
{
    /**
     * @param string $message
     *
     * @Transformer(endpointId="some-id", inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *     @Payload(parameterName="message")
     * }, requiredInterceptorNames={"someReference"})
     * @return string
     */
    public function send(string $message) : string
    {
        return "";
    }
}