<?php

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToStaticValueParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class ServiceActivatorWithAllConfigurationDefined
{
    /**
     * @param string    $to
     * @param string    $content
     * @param Message   $message
     * @param \stdClass $object
     * @param string    $name
     *
     * @return void
     * @ServiceActivatorAnnotation(inputChannelName="inputChannel", outputChannelName="outputChannel", requiresReply=true, parameterConverters={
     *     @MessageToHeaderParameterAnnotation(parameterName="to", headerName="sendTo"),
     *     @MessageToPayloadParameterAnnotation(parameterName="content"),
     *     @MessageParameterAnnotation(parameterName="message"),
     *     @MessageToReferenceServiceAnnotation(parameterName="object", referenceName="reference"),
     *     @MessageToStaticValueParameterAnnotation(parameterName="name", value="some")
     * })
     */
    public function sendMessage(string $to, string $content, Message $message, \stdClass $object, string $name) : void
    {
        return;
    }
}