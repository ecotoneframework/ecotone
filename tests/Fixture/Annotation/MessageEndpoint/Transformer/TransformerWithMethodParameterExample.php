<?php
declare(strict_types=1);

namespace Fixture\Annotation\MessageEndpoint\Transformer;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Transformer;

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
     * @Transformer(inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *     @Payload(parameterName="message")
     * })
     * @return string
     */
    public function send(string $message) : string
    {
        return "";
    }
}