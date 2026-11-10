<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Channel\FinalFailureStrategyChannelBuilder;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseConsumerFeature;
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
final class ErrorHandlerPulseFeatures
{
    /**
     * @param object[] $serviceExtensions
     * @param list<array{string, ErrorHandlerConfiguration}> $endpointDelayedRetries
     * @param list<array{class-string, ErrorHandlerConfiguration}> $gatewayDelayedRetries
     * @return PulseFeature[]
     */
    public static function reportedFor(array $serviceExtensions, ServiceConfiguration $serviceConfiguration, array $endpointDelayedRetries, array $gatewayDelayedRetries): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }
        $delayedRetryTargets = [];
        foreach ($endpointDelayedRetries as [$endpointId, $configuration]) {
            $delayedRetryTargets[] = $endpointId . ': ' . self::scheduleOf($configuration->delayedRetryTemplate, $configuration->deadLetterChannel);
        }
        foreach ($gatewayDelayedRetries as [$gatewayInterface, $configuration]) {
            $delayedRetryTargets[] = PulseFeature::shortNameOf($gatewayInterface) . ': ' . self::scheduleOf($configuration->delayedRetryTemplate, $configuration->deadLetterChannel);
        }
        $defaultErrorChannel = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $serviceExtensions, $serviceConfiguration)->defaultErrorChannel;
        $configurations = ExtensionObjectResolver::resolve(ErrorHandlerConfiguration::class, $serviceExtensions) ?: [ErrorHandlerConfiguration::createDefault()];
        $settings = ['Default error channel' => $defaultErrorChannel ?? 'not set'];
        $retriedChannels = [];
        foreach ($configurations as $configuration) {
            $settings['Retries on ' . $configuration->getErrorChannelName()] = self::scheduleOf($configuration->delayedRetryTemplate, $configuration->deadLetterChannel);
            $retriedChannels[$configuration->getErrorChannelName()] = $settings['Retries on ' . $configuration->getErrorChannelName()];
        }
        $perHandler = ' An endpoint with #[DelayedRetry] retries on its own schedule instead, and #[ErrorChannel] sends its failures to another channel. ErrorHandlerConfiguration::create(errorChannelName, RetryTemplateBuilder) sets the schedule of a channel.';

        return [new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Error channel and delayed retries',
            enabled: $defaultErrorChannel !== null || $delayedRetryTargets !== [],
            settings: $settings,
            handlers: $delayedRetryTargets,
            hint: match (true) {
                $defaultErrorChannel !== null => sprintf("A message an asynchronous endpoint fails on, after its instant retries, is sent to error channel '%s'.", $defaultErrorChannel)
                    . (isset($retriedChannels[$defaultErrorChannel]) ? sprintf(' There it waits and is tried again: %s.', $retriedChannels[$defaultErrorChannel]) : ' No ErrorHandlerConfiguration retries that channel, so only its own subscribers decide what happens to the message.')
                    . $perHandler,
                $delayedRetryTargets !== [] => 'No default error channel is set, so a failing asynchronous message is not retried later, except on the endpoints listed here, which retry on their own schedule (#[DelayedRetry]).' . $perHandler,
                default => 'No default error channel is set, so a message an asynchronous endpoint fails on is not sent to an error channel and is not retried later: what happens to it is decided by the final failure strategy of its channel.' . $perHandler,
            },
            configuration: sprintf("ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('%s')", $defaultErrorChannel ?? $configurations[0]->getErrorChannelName()),
        )];
    }

    /**
     * @param object[] $serviceExtensions
     * @return PulseFeature[]
     */
    public static function finalFailureStrategyFor(array $serviceExtensions): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }
        $strategies = [];
        foreach (ExtensionObjectResolver::resolve(FinalFailureStrategyChannelBuilder::class, $serviceExtensions) as $channel) {
            if ($channel->isPollable()) {
                $strategies['Channel ' . $channel->getMessageChannelName()] = $channel->getFinalFailureStrategy();
            }
        }
        $explained = [
            FinalFailureStrategy::RESEND->value => 'resend: the message is sent back to its channel, so the consumer goes on with the next messages and tries this one again later',
            FinalFailureStrategy::RELEASE->value => 'release: the message is released back to its channel for another attempt, which keeps the order but blocks the consumer while it keeps failing',
            FinalFailureStrategy::IGNORE->value => 'ignore: the message is dropped and the consumer goes on',
            FinalFailureStrategy::STOP->value => 'stop: the consumer stops and the message stays in the channel, to be consumed when the consumer is started again',
        ];
        $used = array_unique(array_map(static fn (FinalFailureStrategy $strategy): string => $strategy->value, $strategies));

        return [new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Final failure strategy',
            enabled: $strategies !== [],
            settings: array_map(static fn (FinalFailureStrategy $strategy): string => $strategy->value, $strategies),
            handlers: [],
            hint: $strategies === []
                ? 'This service consumes no pollable channel. A #[RabbitConsumer] or #[KafkaConsumer] carries its own final failure strategy, stop unless it names another, shown with the consumer under Consumers.'
                : 'The final failure strategy is the last decision for a message that still fails after its retries, when no error channel is set or the error channel fails too. On this service: ' . implode('; ', array_map(static fn (string $strategy): string => $explained[$strategy], $used)) . '. A queue or Kafka channel defaults to resend and an in-memory streaming or RabbitMQ stream channel to release; a #[RabbitConsumer] or #[KafkaConsumer] carries its own strategy, stop unless it names another, shown with the consumer under Consumers.',
            configuration: "->withFinalFailureStrategy(FinalFailureStrategy::STOP) on the channel's builder",
        )];
    }

    /**
     * @param object[] $serviceExtensions
     * @param list<array{string, ErrorHandlerConfiguration}> $endpointDelayedRetries
     * @param list<array{string, string}> $endpointErrorChannels
     * @return PulseConsumerFeature[]
     */
    public static function consumerFeaturesFor(array $serviceExtensions, ServiceConfiguration $serviceConfiguration, array $endpointDelayedRetries, array $endpointErrorChannels): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }
        $defaultErrorChannel = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $serviceExtensions, $serviceConfiguration)->defaultErrorChannel;
        $features = [
            PulseConsumerFeature::global('Error channel', $defaultErrorChannel ?? 'none'),
            PulseConsumerFeature::global('Delayed retries', $defaultErrorChannel === null ? 'none' : self::delayedRetriesOn($serviceExtensions, $defaultErrorChannel)),
        ];
        foreach ($endpointErrorChannels as [$endpointId, $errorChannelName]) {
            $features[] = PulseConsumerFeature::forConsumer($endpointId, 'Error channel', $errorChannelName);
            $features[] = PulseConsumerFeature::forConsumer($endpointId, 'Delayed retries', self::delayedRetriesOn($serviceExtensions, $errorChannelName));
        }
        foreach ($endpointDelayedRetries as [$endpointId, $configuration]) {
            $features[] = PulseConsumerFeature::forConsumer($endpointId, 'Delayed retries', self::scheduleOf($configuration->delayedRetryTemplate, $configuration->deadLetterChannel));
        }
        foreach (ExtensionObjectResolver::resolve(FinalFailureStrategyChannelBuilder::class, $serviceExtensions) as $channel) {
            if ($channel->isPollable() && ! ScheduledPulseFeatures::stopsOnError($serviceExtensions, $channel->getMessageChannelName())) {
                $features[] = $channel->getFinalFailureStrategy() === $channel->getDefaultFinalFailureStrategy()
                    ? PulseConsumerFeature::defaultFor($channel->getMessageChannelName(), 'Final failure strategy', $channel->getFinalFailureStrategy()->value)
                    : PulseConsumerFeature::forConsumer($channel->getMessageChannelName(), 'Final failure strategy', $channel->getFinalFailureStrategy()->value);
            }
        }

        return $features;
    }

    /**
     * @param object[] $serviceExtensions
     */
    public static function delayedRetriesOn(array $serviceExtensions, string $errorChannelName): string
    {
        foreach (ExtensionObjectResolver::resolve(ErrorHandlerConfiguration::class, $serviceExtensions) ?: [ErrorHandlerConfiguration::createDefault()] as $configuration) {
            if ($configuration->getErrorChannelName() === $errorChannelName) {
                return self::scheduleOf($configuration->delayedRetryTemplate, $configuration->deadLetterChannel);
            }
        }

        return 'none';
    }

    private static function scheduleOf(RetryTemplate $retryTemplate, ?string $deadLetterChannel): string
    {
        return PulseFeature::retryScheduleOf($retryTemplate) . ($deadLetterChannel === null ? ', then no further retry' : ', then dead letter channel ' . $deadLetterChannel);
    }
}
