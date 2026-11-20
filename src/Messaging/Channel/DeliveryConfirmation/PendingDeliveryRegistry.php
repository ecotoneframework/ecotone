<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Messaging\Execution\ExecutionScope;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Throwable;
use WeakMap;

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
final class PendingDeliveryRegistry
{
    /** @var WeakMap<self, bool>|null */
    private static ?WeakMap $registriesFlushedOnShutdown = null;

    public function __construct(private LoggingGateway $logger)
    {
    }

    private const PRUNE_INTERVAL = 256;

    private const MAX_UNAWAITED_BACKLOG = 1024;

    public function openScope(): void
    {
        $this->registered()->scopeActive = true;
    }

    public function isScopeActive(): bool
    {
        return $this->registered()->scopeActive;
    }

    public function closeScope(): void
    {
        $this->registered()->scopeActive = false;
        foreach ($this->registered()->pendingDeliveries as $index => $registration) {
            if ($registration['scopeOwned']) {
                if (! $registration['pendingDelivery']->isAwaited()) {
                    $this->awaitAndLogFailures($registration['pendingDelivery']);
                }
                unset($this->registered()->pendingDeliveries[$index]);
            }
        }
    }

    public function markRegisteredSinceAsPublisherOwned(int $collectionPoint): void
    {
        for ($index = $collectionPoint; $index < $this->registered()->nextRegistrationIndex; $index++) {
            if (isset($this->registered()->pendingDeliveries[$index])) {
                $this->registered()->pendingDeliveries[$index]['scopeOwned'] = false;
            }
        }
    }

    public function awaitAll(): DeliveryResult
    {
        $failedDeliveries = [];
        foreach ($this->registered()->pendingDeliveries as $registration) {
            if (! $registration['scopeOwned'] || $registration['pendingDelivery']->isAwaited()) {
                continue;
            }

            $deliveryResult = $registration['pendingDelivery']->awaitDelivery();
            if (! $deliveryResult->isSuccessful()) {
                $failedDeliveries = array_merge($failedDeliveries, $deliveryResult->getFailedDeliveries());
            }
        }

        return $failedDeliveries === [] ? DeliveryResult::successful() : DeliveryResult::withFailedDeliveries($failedDeliveries);
    }

    public function register(string $channelName, PendingDelivery $pendingDelivery): void
    {
        $this->flushUnawaitedDeliveriesOnShutdown();
        if (++$this->registered()->registrationsSinceLastPrune >= self::PRUNE_INTERVAL) {
            $this->pruneAwaitedDeliveries();
            $this->registered()->registrationsSinceLastPrune = 0;
        }
        $this->registered()->pendingDeliveries[$this->registered()->nextRegistrationIndex++] = ['channelName' => $channelName, 'pendingDelivery' => $pendingDelivery, 'scopeOwned' => $this->registered()->scopeActive];
    }

    public function collectionPoint(): int
    {
        return $this->registered()->nextRegistrationIndex;
    }

    /**
     * @return PendingDelivery[]
     */
    public function registeredSince(int $collectionPoint): array
    {
        $registered = [];
        for ($index = $collectionPoint; $index < $this->registered()->nextRegistrationIndex; $index++) {
            if (isset($this->registered()->pendingDeliveries[$index])) {
                $registered[] = $this->registered()->pendingDeliveries[$index]['pendingDelivery'];
            }
        }

        return $registered;
    }

    private function flushUnawaitedDeliveriesOnShutdown(): void
    {
        if (self::$registriesFlushedOnShutdown === null) {
            self::$registriesFlushedOnShutdown = new WeakMap();
            register_shutdown_function(static function (): void {
                foreach (self::$registriesFlushedOnShutdown as $registry => $awaitingFlush) {
                    $registry->flushUnawaitedDeliveries();
                }
            });
        }

        self::$registriesFlushedOnShutdown[$this] = true;
    }

    public function flushUnawaitedDeliveries(): void
    {
        $this->flushUnawaited($this->registered());
    }

    private function flushUnawaited(RegisteredDeliveries $registered): void
    {
        foreach ($registered->pendingDeliveries as $registration) {
            if (! $registration['pendingDelivery']->isAwaited()) {
                $this->awaitAndLogFailures($registration['pendingDelivery']);
            }
        }
        $registered->pendingDeliveries = [];
    }

    private function registered(): RegisteredDeliveries
    {
        return ExecutionScope::stateOf(
            $this,
            fn () => new RegisteredDeliveries(ExecutionScope::isConcurrent() ? $this->flushUnawaited(...) : null)
        );
    }

    private function pruneAwaitedDeliveries(): void
    {
        foreach ($this->registered()->pendingDeliveries as $index => $registration) {
            if ($registration['pendingDelivery']->isAwaited()) {
                unset($this->registered()->pendingDeliveries[$index]);
            }
        }

        $this->flushOldestPublisherOwnedDeliveriesAboveBacklogLimit();
    }

    private function flushOldestPublisherOwnedDeliveriesAboveBacklogLimit(): void
    {
        $exceedingBacklogLimit = count($this->registered()->pendingDeliveries) - self::MAX_UNAWAITED_BACKLOG;
        if ($exceedingBacklogLimit <= 0) {
            return;
        }

        foreach ($this->registered()->pendingDeliveries as $index => $registration) {
            if ($registration['scopeOwned']) {
                continue;
            }

            $this->awaitAndLogFailures($registration['pendingDelivery']);
            unset($this->registered()->pendingDeliveries[$index]);

            if (--$exceedingBacklogLimit <= 0) {
                return;
            }
        }
    }

    private function awaitAndLogFailures(PendingDelivery $pendingDelivery): void
    {
        try {
            $deliveryResult = $pendingDelivery->awaitDelivery();
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Async publishing: awaiting unresolved delivery failed: %s', $exception->getMessage()),
                [],
                ['exception' => $exception],
            );

            return;
        }
        foreach ($deliveryResult->getFailedDeliveries() as $failedDelivery) {
            $this->logger->error(
                sprintf(
                    'Async publishing: unresolved delivery for channel `%s` failed confirmation: %s',
                    $failedDelivery->getChannelName(),
                    $failedDelivery->getFailureReason(),
                ),
                $failedDelivery->getMessage(),
            );
        }
    }
}
