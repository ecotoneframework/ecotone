<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Channel\HighThroughputPublishingChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
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
final class ChannelPulseFeatures
{
    /**
     * @param object[] $serviceExtensions
     * @return PulseFeature[]
     */
    public static function reportedFor(array $serviceExtensions): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }

        return [self::highThroughputPublishing($serviceExtensions), self::resilientSending($serviceExtensions)];
    }

    /**
     * @param object[] $serviceExtensions
     */
    private static function highThroughputPublishing(array $serviceExtensions): PulseFeature
    {
        $settings = [];
        $batched = [];
        foreach (ExtensionObjectResolver::resolve(HighThroughputPublishingChannelBuilder::class, $serviceExtensions) as $channel) {
            $settings['Channel ' . $channel->getMessageChannelName()] = $channel->isBatchPublishingEnabled() ? 'batch publishing' : 'one message at a time';
            if ($channel->isBatchPublishingEnabled()) {
                $batched[] = $channel->getMessageChannelName();
            }
        }

        return new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'High-throughput publishing',
            enabled: $batched !== [],
            settings: $settings,
            handlers: [],
            hint: match (true) {
                $settings === [] => 'No channel of this service supports high-throughput publishing (database, RabbitMQ, SQS, Redis and Kafka channels do), so nothing is published in batches.',
                $batched === [] => 'Each message sent to a broker or database channel is a round trip of its own, and the sender waits for it to be confirmed before sending the next.',
                default => sprintf('On %s: the messages sent in one handler call are written together, in one batch per channel (one multi-row insert for a database channel), and a broker\'s confirmations are awaited before the transaction commits, instead of one blocking round trip per message. The other channels send one message at a time.', implode(', ', $batched))
                    . (($withoutCollector = array_values(array_filter($batched, static fn (string $channel): bool => ! self::collectsFor($serviceExtensions, $channel)))) === [] ? '' : sprintf(' The message collector is off for %s, so messages are sent there as they are produced and no batch is formed.', implode(', ', $withoutCollector))),
            },
            configuration: "->withHighThroughputPublishing() on the channel's builder",
        );
    }

    /**
     * @param object[] $serviceExtensions
     */
    private static function collectsFor(array $serviceExtensions, string $channelName): bool
    {
        foreach (ExtensionObjectResolver::resolve(PollableChannelConfiguration::class, $serviceExtensions) as $channel) {
            if ($channel->getChannelName() === $channelName) {
                return $channel->isCollectorEnabled();
            }
        }

        return ExtensionObjectResolver::resolveUnique(GlobalPollableChannelConfiguration::class, $serviceExtensions, GlobalPollableChannelConfiguration::createWithDefaults())->isCollectorEnabled();
    }

    /**
     * @param object[] $serviceExtensions
     */
    private static function resilientSending(array $serviceExtensions): PulseFeature
    {
        $global = ExtensionObjectResolver::resolveUnique(GlobalPollableChannelConfiguration::class, $serviceExtensions, GlobalPollableChannelConfiguration::createWithDefaults());
        $settings = [
            'Message collector' => PulseFeature::onOff($global->isCollectorEnabled()),
            'Sending retries' => PulseFeature::retryScheduleOf($global->getRetryTemplate()),
            'Error channel for failed sends' => $global->getErrorChannelName() ?? 'none',
        ];
        foreach (ExtensionObjectResolver::resolve(PollableChannelConfiguration::class, $serviceExtensions) as $channel) {
            $settings['Channel ' . $channel->getChannelName()] = sprintf('collector %s, %s, failed sends to %s', PulseFeature::onOff($channel->isCollectorEnabled()), PulseFeature::retryScheduleOf($channel->getRetryTemplate()), $channel->getErrorChannelName() ?? 'nowhere');
        }

        return new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Resilient sending',
            enabled: true,
            settings: $settings,
            handlers: [],
            hint: ($global->isCollectorEnabled()
                ? 'Messages for asynchronous channels are collected and sent only once the handler that produced them has finished, just before its transaction commits, so a failed handler sends none of them. On a database channel in the same transaction they become visible only when it commits; a broker channel receives them just before the commit, so its consumer may read one before the data is committed.'
                : 'Messages for asynchronous channels are sent at once, while the handler is still running, so a handler that fails afterwards has already sent them.')
                . sprintf(' A send that fails is retried: %s%s.', PulseFeature::retryScheduleOf($global->getRetryTemplate()), $global->getErrorChannelName() === null ? ', then the failure reaches the sender' : ", then the message goes to error channel '" . $global->getErrorChannelName() . "'")
                . ' A channel listed here has its own configuration instead.',
            configuration: 'GlobalPollableChannelConfiguration::createWithDefaults()->withCollector(true)',
        );
    }
}
