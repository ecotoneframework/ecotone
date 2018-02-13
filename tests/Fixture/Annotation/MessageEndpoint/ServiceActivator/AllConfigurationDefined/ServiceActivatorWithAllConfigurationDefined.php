<?php

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageParameterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint(referenceName="message_sender")
 */
class ServiceActivatorWithAllConfigurationDefined
{
    /**
     * @param string $to
     * @param string $content
     * @param Message $message
     * @param \stdClass $object
     * @return void
     * @ServiceActivatorAnnotation(inputChannel="inputChannel", outputChannel="outputChannel", requiresReply=true, parameterConverters={
     *     @MessageToHeaderParameterAnnotation(parameterName="to", headerName="sendTo"),
     *     @MessageToPayloadParameterAnnotation(parameterName="content"),
     *     @MessageParameterAnnotation(parameterName="message"),
     *     @MessageToReferenceServiceAnnotation(parameterName="object", referenceName="reference")
     * })
     */
    public function sendMessage(string $to, string $content, Message $message, \stdClass $object) : void
    {
        return;
    }
}