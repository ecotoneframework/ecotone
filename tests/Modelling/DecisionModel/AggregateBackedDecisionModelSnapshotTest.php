<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Test\LicenceTesting;

use function json_decode;
use function json_encode;

use PHPUnit\Framework\TestCase;

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
final class AggregateBackedDecisionModelSnapshotTest extends TestCase
{
    private const SNAPSHOTS = 'decision_model_snapshots_' . WalletBalanceForSnapshotTest::class;
    private const SCOPE_KEY = 'WalletForSnapshot|w-1';

    protected function setUp(): void
    {
        PayoutsForSnapshotTest::$observations = [];
    }

    public function test_snapshotted_model_decides_the_same_as_an_unsnapshotted_one(): void
    {
        $withoutSnapshots = $this->balancesObservedUnder(DynamicConsistencyBoundaryConfiguration::createWithDefaults());
        $withSnapshots = $this->balancesObservedUnder(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withSnapshotsFor(WalletBalanceForSnapshotTest::class, thresholdTrigger: 2)
        );

        $this->assertSame([10, 20, 30, 40, 50], $withoutSnapshots);
        $this->assertSame($withoutSnapshots, $withSnapshots);
    }

    public function test_only_the_events_after_the_snapshot_are_folded(): void
    {
        $this->creditAndObserve($this->bootstrapWithSnapshotsEvery(2), 5);

        $this->assertSame([1, 1, 2, 1, 2], $this->foldedEventCounts());
    }

    public function test_without_snapshots_every_read_folds_the_whole_history(): void
    {
        $this->creditAndObserve($this->bootstrap(DynamicConsistencyBoundaryConfiguration::createWithDefaults()), 5);

        $this->assertSame([1, 2, 3, 4, 5], $this->foldedEventCounts());
    }

    public function test_a_stale_snapshot_decides_like_a_full_fold(): void
    {
        $ecotone = $this->bootstrapWithSnapshotsEvery(1);
        $this->creditAndObserve($ecotone, 1);
        $this->creditWithoutReadingTheModel($ecotone, 19);

        $this->creditAndObserve($ecotone, 1);

        $this->assertSame(210, $this->lastObservedBalance());
        $this->assertSame(20, $this->lastFoldedEventCount());
    }

    public function test_a_corrupt_snapshot_decides_correctly_instead_of_failing(): void
    {
        $ecotone = $this->bootstrapWithSnapshotsEvery(1);
        $this->creditAndObserve($ecotone, 3);
        $this->replaceSnapshot($ecotone, '{"state":"not the model","covered_position":"not a position"}');

        $this->creditAndObserve($ecotone, 1);

        $this->assertSame(40, $this->lastObservedBalance());
        $this->assertSame(4, $this->lastFoldedEventCount());
    }

    public function test_a_snapshot_ahead_of_the_aggregate_is_ignored(): void
    {
        $earlierDeployment = $this->bootstrapWithSnapshotsEvery(1);
        $this->creditAndObserve($earlierDeployment, 3);
        $deploymentWithAFreshEventStore = $this->bootstrapWithSnapshotsEvery(1, $earlierDeployment->getGateway(DocumentStore::class));

        $this->creditAndObserve($deploymentWithAFreshEventStore, 1);

        $this->assertSame(10, $this->lastObservedBalance());
        $this->assertSame(1, $this->lastFoldedEventCount());
    }

    public function test_snapshotting_a_model_nothing_converts_names_the_missing_conversion(): void
    {
        $ecotone = $this->bootstrap(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withSnapshotsFor(WalletBalanceForSnapshotTest::class, thresholdTrigger: 1),
            withConverter: false,
        );

        $this->expectExceptionMessageMatches('/application\/x-php and application\/json/');

        $this->creditAndObserve($ecotone, 1);
    }

    public function test_snapshotting_into_an_unregistered_document_store_names_the_reference(): void
    {
        $ecotone = $this->bootstrap(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withSnapshotsFor(WalletBalanceForSnapshotTest::class, thresholdTrigger: 1, documentStore: 'walletSnapshotStore'),
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches("/'walletSnapshotStore'/");

        $this->creditAndObserve($ecotone, 1);
    }

    /**
     * @return int[]
     */
    private function balancesObservedUnder(DynamicConsistencyBoundaryConfiguration $boundaryConfiguration): array
    {
        PayoutsForSnapshotTest::$observations = [];
        $this->creditAndObserve($this->bootstrap($boundaryConfiguration), 5);

        return array_column(PayoutsForSnapshotTest::$observations, 'balance');
    }

    /**
     * @return int[]
     */
    private function foldedEventCounts(): array
    {
        return array_column(PayoutsForSnapshotTest::$observations, 'foldedEvents');
    }

    private function lastFoldedEventCount(): int
    {
        $foldedEventCounts = $this->foldedEventCounts();

        return end($foldedEventCounts);
    }

    private function lastObservedBalance(): int
    {
        $balances = array_column(PayoutsForSnapshotTest::$observations, 'balance');

        return end($balances);
    }

    private function creditAndObserve(FlowTestSupport $ecotone, int $times): void
    {
        foreach (range(1, $times) as $ignored) {
            $ecotone->sendCommand(new CreditWalletForSnapshotTest('w-1', 10));
            $ecotone->sendCommand(new RequestPayoutForSnapshotTest('w-1', 1));
        }
    }

    private function creditWithoutReadingTheModel(FlowTestSupport $ecotone, int $times): void
    {
        foreach (range(1, $times) as $ignored) {
            $ecotone->sendCommand(new CreditWalletForSnapshotTest('w-1', 10));
        }
    }

    private function replaceSnapshot(FlowTestSupport $ecotone, string $document): void
    {
        $documentStore = $ecotone->getGateway(DocumentStore::class);
        $this->assertSame(1, $documentStore->countDocuments(self::SNAPSHOTS));

        $documentStore->upsertDocument(self::SNAPSHOTS, self::SCOPE_KEY, $document, DocumentStore::LAST_WRITE_WINS);

        $this->assertSame(1, $documentStore->countDocuments(self::SNAPSHOTS));
    }

    private function bootstrapWithSnapshotsEvery(int $thresholdTrigger, ?DocumentStore $documentStore = null): FlowTestSupport
    {
        return $this->bootstrap(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withSnapshotsFor(WalletBalanceForSnapshotTest::class, $thresholdTrigger),
            documentStore: $documentStore,
        );
    }

    private function bootstrap(DynamicConsistencyBoundaryConfiguration $boundaryConfiguration, bool $withConverter = true, ?DocumentStore $documentStore = null): FlowTestSupport
    {
        $handler = new PayoutsForSnapshotTest();
        $services = [$handler, DocumentStore::class => $documentStore ?? InMemoryDocumentStore::createEmpty()];
        if ($withConverter) {
            $services[] = new WalletBalanceConverterForSnapshotTest();
        }

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                PayoutsForSnapshotTest::class,
                WalletForSnapshotTest::class,
                WalletBalanceForSnapshotTest::class,
                WalletOpenedForSnapshotTest::class,
                WalletCreditedForSnapshotTest::class,
                PayoutRequestedForSnapshotTest::class,
                ...($withConverter ? [WalletBalanceConverterForSnapshotTest::class] : []),
            ],
            containerOrAvailableServices: $services,
            extensionObjects: [$boundaryConfiguration],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        return $ecotone->withEventsFor('w-1', WalletForSnapshotTest::class, [new WalletOpenedForSnapshotTest('w-1')]);
    }
}

final readonly class CreditWalletForSnapshotTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class RequestPayoutForSnapshotTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletOpenedForSnapshotTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class WalletCreditedForSnapshotTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class PayoutRequestedForSnapshotTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

#[EventSourcingAggregate]
#[AggregateType('WalletForSnapshot')]
final class WalletForSnapshotTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $walletId;

    #[CommandHandler]
    public function credit(CreditWalletForSnapshotTest $command): array
    {
        return [new WalletCreditedForSnapshotTest($command->walletId, $command->amount)];
    }

    #[Apply]
    public function opened(WalletOpenedForSnapshotTest $event): void
    {
        $this->walletId = $event->walletId;
    }

    #[Apply]
    public function credited(WalletCreditedForSnapshotTest $event): void
    {
        $this->walletId = $event->walletId;
    }
}

#[DecisionModel(aggregate: WalletForSnapshotTest::class)]
final class WalletBalanceForSnapshotTest
{
    private int $balance = 0;

    private int $foldedEvents = 0;

    #[Apply]
    public function credited(WalletCreditedForSnapshotTest $event): void
    {
        $this->balance += $event->amount;
        $this->foldedEvents++;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public function foldedEvents(): int
    {
        return $this->foldedEvents;
    }

    /**
     * @param array{balance: int} $state
     */
    public static function fromArray(array $state): self
    {
        $balance = new self();
        $balance->balance = $state['balance'];

        return $balance;
    }

    /**
     * @return array{balance: int}
     */
    public function toArray(): array
    {
        return ['balance' => $this->balance];
    }
}

#[MediaTypeConverter]
final class WalletBalanceConverterForSnapshotTest implements Converter
{
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $targetMediaType->isCompatibleWith(MediaType::createApplicationJson())
            ? json_encode($source->toArray())
            : WalletBalanceForSnapshotTest::fromArray(json_decode($source, true));
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->getTypeHint() === WalletBalanceForSnapshotTest::class
            || $targetType->getTypeHint() === WalletBalanceForSnapshotTest::class;
    }
}

final class PayoutsForSnapshotTest
{
    /** @var array<int, array{balance: int, foldedEvents: int}> */
    public static array $observations = [];

    #[CommandHandler]
    public function observe(RequestPayoutForSnapshotTest $command, WalletBalanceForSnapshotTest $balance): array
    {
        self::$observations[] = ['balance' => $balance->balance(), 'foldedEvents' => $balance->foldedEvents()];

        return [new PayoutRequestedForSnapshotTest($command->walletId, $command->amount)];
    }
}
