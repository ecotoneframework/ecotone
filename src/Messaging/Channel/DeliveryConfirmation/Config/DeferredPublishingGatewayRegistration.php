<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation\Config;

use Ecotone\Messaging\Channel\DeliveryConfirmation\DeferredPublishingGateway;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PendingDeliveryRegistry;
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
final class DeferredPublishingGatewayRegistration
{
    public static function registerFor(Configuration $messagingConfiguration, string $publisherReferenceName, bool $nonBlockingConfirmationEnabled = true): void
    {
        $publishDeferredRequestChannel = $publisherReferenceName . '.publishDeferred';

        $messagingConfiguration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($publisherReferenceName, MessagePublisher::class, 'publishDeferred', $publishDeferredRequestChannel)
                    ->withParameterConverters([
                        GatewayPayloadBuilder::create('data'),
                        GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        GatewayHeadersBuilder::create('metadata'),
                    ])
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($publishDeferredRequestChannel))
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDefinition(
                    new Definition(DeferredPublishingGateway::class, [
                        $publisherReferenceName,
                        $nonBlockingConfirmationEnabled,
                        new Reference(ConfiguredMessagingSystem::class),
                        new Reference(PendingDeliveryRegistry::class),
                    ]),
                    'publish'
                )
                    ->withInputChannelName($publishDeferredRequestChannel)
                    ->withEndpointId($publishDeferredRequestChannel . '.endpoint')
            );
    }
}
