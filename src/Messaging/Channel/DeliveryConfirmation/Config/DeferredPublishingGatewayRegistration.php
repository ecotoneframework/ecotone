<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation\Config;

use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\MessagePublisher;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\DeliveryConfirmation\DeferredPublishingGateway;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PendingDeliveryRegistry;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
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
                        new Reference(MessageBuilder::class),
                    ]),
                    'publish'
                )
                    ->withInputChannelName($publishDeferredRequestChannel)
                    ->withEndpointId($publishDeferredRequestChannel . '.endpoint')
            );
    }
}
