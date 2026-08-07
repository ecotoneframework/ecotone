<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing\Config;

use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingGateway;
use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;

/**
 * licence Enterprise
 */
final class AsyncPublishGatewayRegistration
{
    public static function registerFor(Configuration $messagingConfiguration, string $publisherReferenceName, bool $asyncPublishingEnabled = true): void
    {
        $asyncPublishRequestChannel = $publisherReferenceName . '.asyncPublish';

        $messagingConfiguration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($publisherReferenceName, MessagePublisher::class, 'asyncPublish', $asyncPublishRequestChannel)
                    ->withParameterConverters([
                        GatewayPayloadBuilder::create('data'),
                        GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        GatewayHeadersBuilder::create('metadata'),
                    ])
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($asyncPublishRequestChannel))
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDefinition(
                    new Definition(AsyncPublishingGateway::class, [
                        $publisherReferenceName,
                        $asyncPublishingEnabled,
                        new Reference(ConfiguredMessagingSystem::class),
                        new Reference(AsyncPublishingRegistry::class),
                    ]),
                    'publish'
                )
                    ->withInputChannelName($asyncPublishRequestChannel)
                    ->withEndpointId($asyncPublishRequestChannel . '.endpoint')
            );
    }
}
