<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function count;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Messaging\Config\ConfigurationException;

use function implode;

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
final class DistributedReplyRouter
{
    /**
     * @param array<string, DistributedServiceMap> $serviceMapsByOutboundChannel
     */
    public function __construct(
        private array $serviceMapsByOutboundChannel,
        private string $thisServiceName,
    ) {
    }

    /**
     * @return array<string>
     */
    public function route(
        #[Header(DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME)]
        ?string $targetedServiceName,
        #[Header(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY)]
        string $replyRoutingKey,
    ): array {
        if ($targetedServiceName === null) {
            throw UnknownDistributedDestination::create("A reply to routing key '{$replyRoutingKey}' cannot be sent, because the message names no source service in header '" . DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME . "', so there is no service to reply to. Send the message with DistributedBus::sendMessage(), which sets that header, or set it to the name of the sending service.");
        }

        $channelsByServiceMap = [];
        $outboundChannelsByChannel = [];
        foreach ($this->serviceMapsByOutboundChannel as $outboundChannel => $serviceMap) {
            if (array_key_exists($targetedServiceName, $serviceMap->commandMapping)) {
                $channel = $serviceMap->commandMapping[$targetedServiceName];
                $channelsByServiceMap[$serviceMap->getReferenceName()] = $channel;
                $outboundChannelsByChannel[$channel] ??= $outboundChannel;
            }
        }

        if ($outboundChannelsByChannel === []) {
            throw UnknownDistributedDestination::create("A reply to routing key '{$replyRoutingKey}' of service '{$targetedServiceName}' cannot be sent, because no Service Map in service '{$this->thisServiceName}' maps service '{$targetedServiceName}' to a channel. Register the channel '{$targetedServiceName}' consumes with DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: '{$targetedServiceName}', channelName: '...') in service '{$this->thisServiceName}', or send the message without replyRoutingKey.");
        }

        if (count($outboundChannelsByChannel) > 1) {
            $mappings = implode(', ', array_map(fn (string $referenceName, string $channel) => "'{$referenceName}' to '{$channel}'", array_keys($channelsByServiceMap), $channelsByServiceMap));

            throw ConfigurationException::create("A reply to routing key '{$replyRoutingKey}' of service '{$targetedServiceName}' cannot be sent, because Service Maps in service '{$this->thisServiceName}' map service '{$targetedServiceName}' to different channels: {$mappings}. Map service '{$targetedServiceName}' in one Service Map only, or to the same channel in each.");
        }

        return array_values($outboundChannelsByChannel);
    }
}
