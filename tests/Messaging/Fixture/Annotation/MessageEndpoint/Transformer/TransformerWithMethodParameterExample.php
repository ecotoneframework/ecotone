<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Transformer;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Transformer;

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