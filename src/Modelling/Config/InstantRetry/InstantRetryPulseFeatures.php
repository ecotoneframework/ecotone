<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
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
final class InstantRetryPulseFeatures
{
    /**
     * @param array<string, InstantRetry> $commandBusOverrides
     * @param list<array{string, InstantRetry}> $endpointOverrides
     * @param list<string> $handlersWithoutTransaction
     */
    private function __construct(
        private InstantRetryConfiguration $configuration,
        private array $commandBusOverrides,
        private array $endpointOverrides,
        private array $handlersWithoutTransaction,
    ) {
    }

    /**
     * @param object[] $serviceExtensions
     * @return self[]
     */
    public static function reportedFor(array $serviceExtensions, InstantRetryPulseAnnotations $annotations): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }

        return [new self(
            configuration: ExtensionObjectResolver::resolveUnique(InstantRetryConfiguration::class, $serviceExtensions, InstantRetryConfiguration::createWithDefaults()),
            commandBusOverrides: $annotations->commandBusOverrides,
            endpointOverrides: $annotations->endpointOverrides,
            handlersWithoutTransaction: $annotations->handlersWithoutTransaction,
        )];
    }

    /**
     * @param list<string> $projectionChannels
     * @return PulseFeature[]
     */
    public function under(InstantRetryDefaults $defaults, array $projectionChannels): array
    {
        return [$this->asynchronousEndpoints($defaults, $projectionChannels), $this->commandBus($defaults)];
    }

    /**
     * @param list<string> $projectionChannels
     * @return PulseConsumerFeature[]
     */
    public function consumerFeaturesUnder(InstantRetryDefaults $defaults, array $projectionChannels): array
    {
        $isEnabled = $this->configuration->isEnabledForAsynchronousEndpoints ?? $defaults->forAsynchronousEndpoints();
        $byDefault = $this->configuration->isEnabledForAsynchronousEndpoints === null;
        $global = $isEnabled
            ? sprintf('%s on %s', PulseFeature::retriesOf($this->configuration->asynchronousRetryTimes), PulseFeature::exceptionsOf($this->configuration->asynchronousExceptions)) . ($byDefault ? ', in a database transaction' : '')
            : 'off';
        $withoutTransaction = $byDefault && $isEnabled ? array_diff([...$defaults->databaseTransactions->asynchronousEndpointsWithoutTransaction, ...$this->handlersWithoutTransaction], $projectionChannels) : [];

        return [
            PulseConsumerFeature::global('Instant retries', $global),
            ...array_map(static fn (string $consumerOrEndpointId): PulseConsumerFeature => PulseConsumerFeature::forConsumer($consumerOrEndpointId, 'Instant retries', 'off, runs without a database transaction'), $withoutTransaction),
            ...array_map(static fn (array $override): PulseConsumerFeature => PulseConsumerFeature::forConsumer($override[0], 'Instant retries', sprintf('%s on %s', PulseFeature::retriesOf($override[1]->retryTimes), PulseFeature::exceptionsOf($override[1]->exceptions))), $this->endpointOverrides),
            ...($byDefault && $defaults->apply ? array_map(fn (string $channel): PulseConsumerFeature => PulseConsumerFeature::defaultFor($channel, 'Instant retries', sprintf('%s on %s, as the projection manages its own transaction', PulseFeature::retriesOf($this->configuration->asynchronousRetryTimes), PulseFeature::exceptionsOf($this->configuration->asynchronousExceptions))), $projectionChannels) : []),
        ];
    }

    /**
     * @param list<string> $projectionChannels
     */
    private function asynchronousEndpoints(InstantRetryDefaults $defaults, array $projectionChannels): PulseFeature
    {
        $retryTimes = $this->configuration->asynchronousRetryTimes;
        $exceptions = PulseFeature::exceptionsOf($this->configuration->asynchronousExceptions);
        $excluded = array_values(array_diff($defaults->databaseTransactions->asynchronousEndpointsWithoutTransaction, $projectionChannels));
        $isEnabled = $this->configuration->isEnabledForAsynchronousEndpoints ?? $defaults->forAsynchronousEndpoints();
        $override = ' An endpoint with #[InstantRetry] uses its own count instead.';

        return new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Instant retries on asynchronous endpoints',
            enabled: $isEnabled,
            settings: $isEnabled ? [
                'Retry times' => (string) $retryTimes,
                'Retried on' => $exceptions,
                ...($this->configuration->isEnabledForAsynchronousEndpoints === null ? ['Applied' => 'by default, to endpoints running in a database transaction' . ($excluded === [] ? '' : ', except ' . implode(', ', $excluded))] : []),
            ] : [],
            handlers: array_map(static fn (array $override): string => self::overrideOf($override[0], $override[1]), $this->endpointOverrides),
            hint: match (true) {
                $this->configuration->isEnabledForAsynchronousEndpoints === true => sprintf('A message an asynchronous endpoint fails on is handled once and, when that fails, tried again right away up to %d more times (%d attempts in total) on %s, before the failure is handed on to the error channel, when one is set.', $retryTimes, $retryTimes + 1, $exceptions) . $override,
                $this->configuration->isEnabledForAsynchronousEndpoints === false => 'A message an asynchronous endpoint fails on is handed straight on to the error channel, when one is set, with no immediate second attempt.' . $override,
                ! $defaults->apply => 'Not configured, and TestConfiguration::withDefaultInstantRetries(false) switches the default off: a message an asynchronous endpoint fails on is handed straight on to the error channel, when one is set, with no immediate second attempt.' . $override,
                $isEnabled => sprintf('Not configured, so the default applies: a message an asynchronous endpoint consumes in a database transaction is handled once and, when that fails, its transaction is rolled back and it is tried again right away up to %d more times (%d attempts in total) on %s, before the failure is handed on to the error channel, when one is set. An endpoint that runs without a transaction (#[WithoutDatabaseTransaction] or withoutTransactionOnAsynchronousEndpoints) is not retried, except an asynchronous projection, which manages its own transaction.', $retryTimes, $retryTimes + 1, $exceptions) . $override,
                default => 'Not configured, and asynchronous endpoints run without a database transaction, so the default retries only asynchronous projections, which manage their own transaction: a message any other endpoint fails on is handed straight on to the error channel, when one is set.' . $override,
            },
            configuration: sprintf('InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(isEnabled: true, retryTimes: %d, retryExceptions: %s)', $retryTimes, PulseFeature::exceptionClassesOf($this->configuration->asynchronousExceptions)),
        );
    }

    private function commandBus(InstantRetryDefaults $defaults): PulseFeature
    {
        $retryTimes = $this->configuration->commandBusRetryTimes;
        $exceptions = PulseFeature::exceptionsOf($this->configuration->commandBuExceptions);
        $isEnabled = $this->configuration->isEnabledForCommandBus ?? $defaults->forCommandBus();
        $override = ' A CommandBus interface with #[InstantRetry] uses its own count instead.';

        return new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Instant retries on the command bus',
            enabled: $isEnabled,
            settings: $isEnabled ? [
                'Retry times' => (string) $retryTimes,
                'Retried on' => $exceptions,
                ...($this->configuration->isEnabledForCommandBus === null ? ['Applied' => 'by default, to commands handled in a database transaction'] : []),
            ] : [],
            handlers: array_map(static fn (string $commandBus, InstantRetry $instantRetry): string => self::overrideOf(PulseFeature::shortNameOf($commandBus), $instantRetry), array_keys($this->commandBusOverrides), array_values($this->commandBusOverrides)),
            hint: match (true) {
                $this->configuration->isEnabledForCommandBus === true => sprintf('A command sent through the command bus is handled once and, when that fails, tried again right away up to %d more times (%d attempts in total) on %s, before the exception reaches the caller.', $retryTimes, $retryTimes + 1, $exceptions) . $override,
                $this->configuration->isEnabledForCommandBus === false => 'A command sent through the command bus is sent once: when its handler fails, the exception reaches the caller with no immediate second attempt.' . $override,
                ! $defaults->apply => 'Not configured, and TestConfiguration::withDefaultInstantRetries(false) switches the default off: when a command handler fails, the exception reaches the caller with no immediate second attempt.' . $override,
                $isEnabled => sprintf('Not configured, so the default applies: a command sent through the command bus is handled once in a database transaction and, when that fails, its transaction is rolled back and it is tried again right away up to %d more times (%d attempts in total) on %s, before the exception reaches the caller. A command whose handler has #[WithoutDatabaseTransaction] is not retried.', $retryTimes, $retryTimes + 1, $exceptions) . $override,
                default => 'Not configured, and the command bus runs without a database transaction, so the default does not retry: when a command handler fails, the exception reaches the caller with no immediate second attempt.' . $override,
            },
            configuration: sprintf('InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(isEnabled: true, retryTimes: %d, retryExceptions: %s)', $retryTimes, PulseFeature::exceptionClassesOf($this->configuration->commandBuExceptions)),
        );
    }

    private static function overrideOf(string $target, InstantRetry $instantRetry): string
    {
        return sprintf('%s: %d retry times on %s', $target, $instantRetry->retryTimes, PulseFeature::exceptionsOf($instantRetry->exceptions));
    }
}
