<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
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
final class TagScopedDecisionModelSnapshotTest extends TestCase
{
    private const SNAPSHOTS = 'decision_model_snapshots_' . SpentTodayForTagSnapshotTest::class;
    private const SCOPE_KEY = 'wallet|w-1';

    protected function setUp(): void
    {
        WalletsForTagSnapshotTest::$observations = [];
    }

    public function test_snapshotted_model_decides_the_same_as_an_unsnapshotted_one(): void
    {
        $withoutSnapshots = $this->balancesObservedUnder(DynamicConsistencyBoundaryConfiguration::createWithDefaults());
        $withSnapshots = $this->balancesObservedUnder(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withSnapshotsFor(SpentTodayForTagSnapshotTest::class, thresholdTrigger: 2)
        );

        $this->assertSame([0, 10, 20, 30, 40, 50], $withoutSnapshots);
        $this->assertSame($withoutSnapshots, $withSnapshots);
    }

    public function test_only_the_events_after_the_snapshot_are_folded(): void
    {
        $this->credit($this->bootstrapWithSnapshotsEvery(2), 6);

        $this->assertSame([0, 1, 2, 1, 2, 1], $this->foldedEventCounts());
    }

    public function test_without_snapshots_every_read_folds_the_whole_scope(): void
    {
        $this->credit($this->bootstrap(DynamicConsistencyBoundaryConfiguration::createWithDefaults()), 6);

        $this->assertSame([0, 1, 2, 3, 4, 5], $this->foldedEventCounts());
    }

    public function test_a_stale_snapshot_decides_like_a_full_fold(): void
    {
        $ecotone = $this->bootstrapWithSnapshotsEvery(1);
        $this->credit($ecotone, 2);
        $ecotone->withEvents(array_fill(0, 18, new WalletCreditedForTagSnapshotTest('w-1', 10)));

        $this->credit($ecotone, 1);

        $this->assertSame(200, $this->lastObservedBalance());
        $this->assertSame(19, $this->lastFoldedEventCount());
    }

    public function test_a_corrupt_snapshot_decides_correctly_instead_of_failing(): void
    {
        $ecotone = $this->bootstrapWithSnapshotsEvery(1);
        $this->credit($ecotone, 3);
        $this->replaceSnapshot($ecotone, '{"state":"not the model","covered_position":"not a position"}');

        $this->credit($ecotone, 1);

        $this->assertSame(30, $this->lastObservedBalance());
        $this->assertSame(3, $this->lastFoldedEventCount());
    }

    public function test_a_snapshot_ahead_of_the_captured_counter_is_ignored(): void
    {
        $earlierDeployment = $this->bootstrapWithSnapshotsEvery(1);
        $this->credit($earlierDeployment, 3);
        $deploymentWithAFreshEventStore = $this->bootstrapWithSnapshotsEvery(1, $earlierDeployment->getGateway(DocumentStore::class));

        $this->credit($deploymentWithAFreshEventStore, 2);

        $this->assertSame(10, $this->lastObservedBalance());
        $this->assertSame(1, $this->lastFoldedEventCount());
    }

    public function test_the_position_tag_of_a_model_whose_first_tag_is_filter_only_is_the_first_counted_one(): void
    {
        $ecotone = $this->bootstrapRegionalWithSnapshotsEvery(2);

        foreach (range(1, 6) as $ignored) {
            $ecotone->sendCommand(new CreditRegionalWalletForTagSnapshotTest('eu', 'w-1', 10));
        }

        $this->assertSame([0, 10, 20, 30, 40, 50], array_column(WalletsForTagSnapshotTest::$observations, 'spent'));
        $this->assertSame([0, 1, 2, 1, 2, 1], $this->foldedEventCounts());
    }

    /**
     * @return int[]
     */
    private function balancesObservedUnder(DynamicConsistencyBoundaryConfiguration $boundaryConfiguration): array
    {
        WalletsForTagSnapshotTest::$observations = [];
        $this->credit($this->bootstrap($boundaryConfiguration), 6);

        return array_column(WalletsForTagSnapshotTest::$observations, 'spent');
    }

    /**
     * @return int[]
     */
    private function foldedEventCounts(): array
    {
        return array_column(WalletsForTagSnapshotTest::$observations, 'foldedEvents');
    }

    private function lastFoldedEventCount(): int
    {
        $foldedEventCounts = $this->foldedEventCounts();

        return end($foldedEventCounts);
    }

    private function lastObservedBalance(): int
    {
        $spent = array_column(WalletsForTagSnapshotTest::$observations, 'spent');

        return end($spent);
    }

    private function credit(FlowTestSupport $ecotone, int $times): void
    {
        foreach (range(1, $times) as $ignored) {
            $ecotone->sendCommand(new CreditWalletForTagSnapshotTest('w-1', 10));
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
                ->withSnapshotsFor(SpentTodayForTagSnapshotTest::class, $thresholdTrigger),
            documentStore: $documentStore,
        );
    }

    private function bootstrapRegionalWithSnapshotsEvery(int $thresholdTrigger): FlowTestSupport
    {
        return $this->bootstrap(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withFilterOnlyTags(['region'])
                ->withSnapshotsFor(RegionalSpentTodayForTagSnapshotTest::class, $thresholdTrigger),
            [RegionalSpentTodayForTagSnapshotTest::class, RegionalWalletCreditedForTagSnapshotTest::class],
        );
    }

    /**
     * @param class-string[] $extraClasses
     */
    private function bootstrap(DynamicConsistencyBoundaryConfiguration $boundaryConfiguration, array $extraClasses = [], ?DocumentStore $documentStore = null): FlowTestSupport
    {
        $handler = new WalletsForTagSnapshotTest();

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                WalletsForTagSnapshotTest::class,
                SpentTodayForTagSnapshotTest::class,
                WalletCreditedForTagSnapshotTest::class,
                SpentTodayConverterForTagSnapshotTest::class,
                ...$extraClasses,
            ],
            containerOrAvailableServices: [
                $handler,
                new SpentTodayConverterForTagSnapshotTest(),
                DocumentStore::class => $documentStore ?? InMemoryDocumentStore::createEmpty(),
            ],
            extensionObjects: [$boundaryConfiguration],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class CreditWalletForTagSnapshotTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class CreditRegionalWalletForTagSnapshotTest
{
    public function __construct(
        public string $region,
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletCreditedForTagSnapshotTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class RegionalWalletCreditedForTagSnapshotTest
{
    public function __construct(
        #[EventTag('region')] public string $region,
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

#[DecisionModel]
final class SpentTodayForTagSnapshotTest
{
    private int $spent = 0;

    private int $foldedEvents = 0;

    #[Apply]
    public function credited(WalletCreditedForTagSnapshotTest $event): void
    {
        $this->spent += $event->amount;
        $this->foldedEvents++;
    }

    public function spent(): int
    {
        return $this->spent;
    }

    public function foldedEvents(): int
    {
        return $this->foldedEvents;
    }

    /**
     * @return array{spent: int}
     */
    public function toArray(): array
    {
        return ['spent' => $this->spent];
    }

    /**
     * @param array{spent: int} $state
     */
    public static function fromArray(array $state): self
    {
        $spentToday = new self();
        $spentToday->spent = $state['spent'];

        return $spentToday;
    }
}

#[DecisionModel(tags: ['region', 'wallet'])]
final class RegionalSpentTodayForTagSnapshotTest
{
    private int $spent = 0;

    private int $foldedEvents = 0;

    #[Apply]
    public function credited(RegionalWalletCreditedForTagSnapshotTest $event): void
    {
        $this->spent += $event->amount;
        $this->foldedEvents++;
    }

    public function spent(): int
    {
        return $this->spent;
    }

    public function foldedEvents(): int
    {
        return $this->foldedEvents;
    }

    /**
     * @return array{spent: int}
     */
    public function toArray(): array
    {
        return ['spent' => $this->spent];
    }

    /**
     * @param array{spent: int} $state
     */
    public static function fromArray(array $state): self
    {
        $spentToday = new self();
        $spentToday->spent = $state['spent'];

        return $spentToday;
    }
}

#[MediaTypeConverter]
final class SpentTodayConverterForTagSnapshotTest implements Converter
{
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if ($targetMediaType->isCompatibleWith(MediaType::createApplicationJson())) {
            return json_encode($source->toArray());
        }

        return $targetType->getTypeHint() === RegionalSpentTodayForTagSnapshotTest::class
            ? RegionalSpentTodayForTagSnapshotTest::fromArray(json_decode($source, true))
            : SpentTodayForTagSnapshotTest::fromArray(json_decode($source, true));
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return in_array($sourceType->getTypeHint(), [SpentTodayForTagSnapshotTest::class, RegionalSpentTodayForTagSnapshotTest::class], true)
            || in_array($targetType->getTypeHint(), [SpentTodayForTagSnapshotTest::class, RegionalSpentTodayForTagSnapshotTest::class], true);
    }
}

final class WalletsForTagSnapshotTest
{
    /** @var array<int, array{spent: int, foldedEvents: int}> */
    public static array $observations = [];

    #[CommandHandler]
    public function credit(CreditWalletForTagSnapshotTest $command, SpentTodayForTagSnapshotTest $spentToday): array
    {
        self::$observations[] = ['spent' => $spentToday->spent(), 'foldedEvents' => $spentToday->foldedEvents()];

        return [new WalletCreditedForTagSnapshotTest($command->walletId, $command->amount)];
    }

    #[CommandHandler]
    public function creditRegional(CreditRegionalWalletForTagSnapshotTest $command, RegionalSpentTodayForTagSnapshotTest $spentToday): array
    {
        self::$observations[] = ['spent' => $spentToday->spent(), 'foldedEvents' => $spentToday->foldedEvents()];

        return [new RegionalWalletCreditedForTagSnapshotTest($command->region, $command->walletId, $command->amount)];
    }
}
