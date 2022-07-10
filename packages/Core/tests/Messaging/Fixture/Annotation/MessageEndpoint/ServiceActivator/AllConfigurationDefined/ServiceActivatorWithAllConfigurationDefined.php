<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\ServiceActivator;
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
    public function sendMessage(#[Header("sendTo")] string $to, #[Payload] string $content, Message $message, #[Reference] \stdClass $object, #[Header("token", "value")] ?string $name, #[ConfigurationVariable("env")] string $environment): void
    {
    }
}