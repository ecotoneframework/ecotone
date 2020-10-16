<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\MessageParameter;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Message;

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
     *     @Reference(parameterName="object", referenceName="reference"),
     *     @Header(parameterName="name", headerName="token", expression="value", isRequired=false)
     * }, requiredInterceptorNames={"someReference"})
     */
    public function sendMessage(string $to, string $content, Message $message, \stdClass $object, string $name): void
    {
    }
}