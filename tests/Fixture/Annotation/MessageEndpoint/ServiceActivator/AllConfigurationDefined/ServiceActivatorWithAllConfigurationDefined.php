<?php

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\HeaderParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\MessageParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\PayloadParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\ReferenceServiceConverterAnnotation;
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
     * @ServiceActivatorAnnotation(inputChannel="inputChannel", outputChannel="outputChannel", requiresReply=true, parameterConverters={@HeaderParameterConverterAnnotation(parameterName="to", headerName="sendTo"), @PayloadParameterConverterAnnotation(parameterName="content"), @MessageParameterConverterAnnotation(parameterName="message"), @ReferenceServiceConverterAnnotation(parameterName="object", referenceName="reference")})
     */
    public function sendMessage(string $to, string $content, Message $message, \stdClass $object) : void
    {
        return;
    }
}