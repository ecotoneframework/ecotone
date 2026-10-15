<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use Ecotone\Api\Attribute\ConfigurationVariable;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Messaging\Message;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceActivatorWithAllConfigurationDefined
{
    #[InternalHandler(
        endpointId: 'test-name',
        inputChannelName: 'inputChannel',
        outputChannelName: 'outputChannel',
        requiresReply: true,
        requiredInterceptorNames: ['someReference']
    )]
    public function sendMessage(#[Header('sendTo')] string $to, #[Payload] string $content, Message $message, #[Reference] stdClass $object, #[Header('token', 'value')] ?string $name, #[ConfigurationVariable('env')] string $environment): void
    {
    }
}
