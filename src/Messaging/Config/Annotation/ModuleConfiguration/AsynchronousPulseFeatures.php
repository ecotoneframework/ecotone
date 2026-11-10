<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseFeature;

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
 *
 * @internal
 */
final class AsynchronousPulseFeatures
{
    /**
     * @param object[] $serviceExtensions
     * @param array<string, string|string[]> $asynchronousEndpoints
     * @return PulseFeature[]
     */
    public static function reportedFor(array $serviceExtensions, array $asynchronousEndpoints): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }
        $combined = [];
        foreach (ExtensionObjectResolver::resolve(CombinedMessageChannel::class, $serviceExtensions) as $combinedChannel) {
            $combined[$combinedChannel->getReferenceName()] = $combinedChannel->combinedChannels;
        }
        $handlers = [];
        foreach ($asynchronousEndpoints as $endpointId => $channels) {
            $channels = (array) $channels;
            if (count($channels) > 1 || array_key_exists($channels[0], $combined)) {
                $handlers[] = $endpointId . ': ' . implode(', then ', $channels);
            }
        }

        return [new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Combined channels (outbox)',
            enabled: $combined !== [] || $handlers !== [],
            settings: array_combine(
                array_map(static fn (string $referenceName): string => 'Channel ' . $referenceName, array_keys($combined)),
                array_map(static fn (array $channels): string => implode(', then ', $channels), array_values($combined)),
            ),
            handlers: $handlers,
            hint: $combined === [] && $handlers === []
                ? 'Every asynchronous handler reads from a single channel. With a combined channel whose first channel is a database channel, a message is stored in the same transaction as the data it is about (the outbox pattern) and relayed to a broker after the commit.'
                : 'A message sent to a combined channel, or to a handler with several channels, goes through each of its channels in turn, in the order listed. When the first is a database channel, the message is stored in the same transaction as the data change and only moves on to the next channel once that transaction has committed, so a broker never sees a message whose data was rolled back (the outbox pattern).',
            configuration: "CombinedMessageChannel::create('orders', ['outbox', 'orders_broker'])",
        )];
    }
}
