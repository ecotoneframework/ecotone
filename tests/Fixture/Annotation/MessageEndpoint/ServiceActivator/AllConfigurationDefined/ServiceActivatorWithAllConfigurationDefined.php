<?php

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Message;

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