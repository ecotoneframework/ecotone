<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Message;

class ServiceActivatorWithAllConfigurationDefined
{
    #[ServiceActivator(
        endpointId: "test-name",
        inputChannelName: "inputChannel",
        outputChannelName: "outputChannel",
        requiresReply: true,
        requiredInterceptorNames: ["someReference"]
    )]
    public function sendMessage(#[Header("sendTo")] string $to, #[Payload] string $content, Message $message, #[Reference] \stdClass $object, #[Header("token", "value")] ?string $name): void
    {
    }
}