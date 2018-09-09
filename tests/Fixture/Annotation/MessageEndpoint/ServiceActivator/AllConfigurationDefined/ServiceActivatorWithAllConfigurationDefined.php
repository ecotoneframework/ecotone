<?php

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointId;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
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
     * @ServiceActivator(endpointId="test-name", inputChannelName="inputChannel", outputChannelName="outputChannel", requiresReply=true, parameterConverters={
     *     @Header(parameterName="to", headerName="sendTo"),
     *     @Payload(parameterName="content"),
     *     @MessageParameter(parameterName="message"),
     *     @Reference(parameterName="object", referenceName="reference"),
     *     @Value(parameterName="name", value="some")
     * })
     */
    public function sendMessage(string $to, string $content, Message $message, \stdClass $object, string $name) : void
    {
        return;
    }
}