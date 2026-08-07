<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Throwable;
use WeakMap;

/**
 * licence Enterprise
 */
final class AsyncPublishingRegistry
{
    /** @var array<int, array{channelName: string, pendingDelivery: PendingDelivery, scopeOwned: bool}> */
    private array $pendingDeliveries = [];

    /** @var WeakMap<self, bool>|null */
    private static ?WeakMap $registriesFlushedOnShutdown = null;

    public function __construct(private LoggingGateway $logger)
    {
    }

    private const PRUNE_INTERVAL = 256;

    private const MAX_UNAWAITED_BACKLOG = 1024;

    private int $nextRegistrationIndex = 0;

    private int $registrationsSinceLastPrune = 0;

    private bool $scopeActive = false;

    public function openScope(): void
    {
        $this->scopeActive = true;
    }

    public function isScopeActive(): bool
    {
        return $this->scopeActive;
    }

    public function closeScope(): void
    {
        $this->scopeActive = false;
        foreach ($this->pendingDeliveries as $index => $registration) {
            if ($registration['scopeOwned']) {
                if (! $registration['pendingDelivery']->isAwaited()) {
                    $this->awaitAndLogFailures($registration['pendingDelivery']);
                }
                unset($this->pendingDeliveries[$index]);
            }
        }
    }

    public function markRegisteredSinceAsPublisherOwned(int $collectionPoint): void
    {
        for ($index = $collectionPoint; $index < $this->nextRegistrationIndex; $index++) {
            if (isset($this->pendingDeliveries[$index])) {
                $this->pendingDeliveries[$index]['scopeOwned'] = false;
            }
        }
    }

    public function awaitAll(): DeliveryResult
    {
        $failedDeliveries = [];
        foreach ($this->pendingDeliveries as $registration) {
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
        if (++$this->registrationsSinceLastPrune >= self::PRUNE_INTERVAL) {
            $this->pruneAwaitedDeliveries();
            $this->registrationsSinceLastPrune = 0;
        }
        $this->pendingDeliveries[$this->nextRegistrationIndex++] = ['channelName' => $channelName, 'pendingDelivery' => $pendingDelivery, 'scopeOwned' => $this->scopeActive];
    }

    public function collectionPoint(): int
    {
        return $this->nextRegistrationIndex;
    }

    /**
     * @return PendingDelivery[]
     */
    public function registeredSince(int $collectionPoint): array
    {
        $registered = [];
        for ($index = $collectionPoint; $index < $this->nextRegistrationIndex; $index++) {
            if (isset($this->pendingDeliveries[$index])) {
                $registered[] = $this->pendingDeliveries[$index]['pendingDelivery'];
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
        foreach ($this->pendingDeliveries as $registration) {
            if (! $registration['pendingDelivery']->isAwaited()) {
                $this->awaitAndLogFailures($registration['pendingDelivery']);
            }
        }
        $this->pendingDeliveries = [];
    }

    private function pruneAwaitedDeliveries(): void
    {
        foreach ($this->pendingDeliveries as $index => $registration) {
            if ($registration['pendingDelivery']->isAwaited()) {
                unset($this->pendingDeliveries[$index]);
            }
        }

        $this->flushOldestPublisherOwnedDeliveriesAboveBacklogLimit();
    }

    private function flushOldestPublisherOwnedDeliveriesAboveBacklogLimit(): void
    {
        $exceedingBacklogLimit = count($this->pendingDeliveries) - self::MAX_UNAWAITED_BACKLOG;
        if ($exceedingBacklogLimit <= 0) {
            return;
        }

        foreach ($this->pendingDeliveries as $index => $registration) {
            if ($registration['scopeOwned']) {
                continue;
            }

            $this->awaitAndLogFailures($registration['pendingDelivery']);
            unset($this->pendingDeliveries[$index]);

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
