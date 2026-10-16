<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
final class AggregateBackedDecisionModelTest extends TestCase
{
    private const CLASSES = [
        PayoutsForAggregateBackedTest::class,
        WalletForAggregateBackedTest::class,
        LedgerForAggregateBackedTest::class,
        WalletBalanceForAggregateBackedTest::class,
        WalletCreditCountForAggregateBackedTest::class,
        LedgerBalanceForAggregateBackedTest::class,
        PayoutsTodayForAggregateBackedTest::class,
        WalletCreditedForAggregateBackedTest::class,
        WalletDebitedForAggregateBackedTest::class,
        WalletFrozenForAggregateBackedTest::class,
        LedgerCreditedForAggregateBackedTest::class,
        PayoutRequestedForAggregateBackedTest::class,
    ];

    protected function setUp(): void
    {
        PayoutsForAggregateBackedTest::$observed = [];
    }

    public function test_model_folds_the_backing_aggregates_history_although_no_event_carries_an_event_tag(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
        ]);

        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 60));

        $this->assertSame(100, PayoutsForAggregateBackedTest::$observed['balance']);
    }

    public function test_a_payout_beyond_the_folded_balance_is_refused(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
            new WalletDebitedForAggregateBackedTest('w-1', 80),
        ]);

        $this->expectException(InsufficientFundsForAggregateBackedTest::class);
        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 60));
    }

    public function test_only_the_event_types_the_model_handles_are_folded(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
            new WalletFrozenForAggregateBackedTest('w-1'),
            new WalletDebitedForAggregateBackedTest('w-1', 40),
        ]);

        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 10));

        $this->assertSame(2, PayoutsForAggregateBackedTest::$observed['foldedEvents']);
        $this->assertSame(60, PayoutsForAggregateBackedTest::$observed['balance']);
    }

    public function test_events_are_folded_in_aggregate_version_order(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
            new WalletDebitedForAggregateBackedTest('w-1', 30),
            new WalletCreditedForAggregateBackedTest('w-1', 5),
        ]);

        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 10));

        $this->assertSame([100, 70, 75], PayoutsForAggregateBackedTest::$observed['runningBalances']);
    }

    public function test_identifier_resolved_by_convention_from_a_property_suffixed_with_id(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('l-1', LedgerForAggregateBackedTest::class, [
            new LedgerCreditedForAggregateBackedTest('l-1', 42),
        ]);

        $ecotone->sendCommand(new AuditLedgerForAggregateBackedTest('l-1'));

        $this->assertSame(42, PayoutsForAggregateBackedTest::$observed['ledgerBalance']);
    }

    public function test_fetch_maps_the_same_aggregate_backed_model_class_injected_twice(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [new WalletCreditedForAggregateBackedTest('w-1', 100)]);
        $ecotone->withEventsFor('w-2', WalletForAggregateBackedTest::class, [new WalletCreditedForAggregateBackedTest('w-2', 7)]);

        $ecotone->sendCommand(new TransferMoneyForAggregateBackedTest('w-1', 'w-2', 5));

        $this->assertSame(['from' => 100, 'to' => 7], PayoutsForAggregateBackedTest::$observed['transfer']);
    }

    public function test_fetch_expression_resolving_a_map_scopes_the_model(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-3', WalletForAggregateBackedTest::class, [new WalletCreditedForAggregateBackedTest('w-3', 33)]);

        $ecotone->sendCommand(new AuditWalletForAggregateBackedTest('w-3'));

        $this->assertSame(33, PayoutsForAggregateBackedTest::$observed['auditedBalance']);
    }

    public function test_nullable_model_parameter_receives_null_and_the_handler_still_appends(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommand(new CloseDayForAggregateBackedTest(null));

        $this->assertFalse(PayoutsForAggregateBackedTest::$observed['walletWasLoaded']);
        $this->assertCount(1, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_an_aggregate_without_any_event_folds_from_nothing(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-never-opened', 0));

        $this->assertSame(0, PayoutsForAggregateBackedTest::$observed['balance']);
        $this->assertCount(1, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_two_models_backed_by_the_same_aggregate_instance_both_fold_its_history(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
            new WalletDebitedForAggregateBackedTest('w-1', 30),
            new WalletCreditedForAggregateBackedTest('w-1', 10),
        ]);

        $ecotone->sendCommand(new ReviewWalletForAggregateBackedTest('w-1'));

        $this->assertSame(['balance' => 80, 'credits' => 2], PayoutsForAggregateBackedTest::$observed['review']);
    }

    public function test_an_aggregate_backed_and_a_tag_scoped_model_in_one_handler_are_both_folded(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [new WalletCreditedForAggregateBackedTest('w-1', 100)]);

        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 10));
        $ecotone->sendCommand(new RequestPayoutForAggregateBackedTest('w-1', 10));

        $this->assertSame(100, PayoutsForAggregateBackedTest::$observed['balance']);
        $this->assertSame(10, PayoutsForAggregateBackedTest::$observed['paidOutToday']);
    }

    public function test_a_query_handler_reads_the_backing_aggregates_current_state_without_appending(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->withEventsFor('w-1', WalletForAggregateBackedTest::class, [
            new WalletCreditedForAggregateBackedTest('w-1', 100),
            new WalletDebitedForAggregateBackedTest('w-1', 25),
        ]);

        $this->assertSame(75, $ecotone->sendQuery(new CheckWalletBalanceForAggregateBackedTest('w-1')));
        $this->assertCount(2, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    private function bootstrap(): FlowTestSupport
    {
        $handler = new PayoutsForAggregateBackedTest();

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: self::CLASSES,
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

/**
 * @internal
 */
final class InsufficientFundsForAggregateBackedTest extends RuntimeException
{
}

final readonly class WalletCreditedForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletDebitedForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletFrozenForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class LedgerCreditedForAggregateBackedTest
{
    public function __construct(
        public string $ledgerId,
        public int $amount,
    ) {
    }
}

final readonly class PayoutRequestedForAggregateBackedTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class RequestPayoutForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class TransferMoneyForAggregateBackedTest
{
    public function __construct(
        public string $fromWalletId,
        public string $toWalletId,
        public int $amount,
    ) {
    }
}

final readonly class AuditWalletForAggregateBackedTest
{
    public function __construct(
        public string $auditedWalletId,
    ) {
    }
}

final readonly class AuditLedgerForAggregateBackedTest
{
    public function __construct(
        public string $ledgerId,
    ) {
    }
}

final readonly class CheckWalletBalanceForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class ReviewWalletForAggregateBackedTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class CloseDayForAggregateBackedTest
{
    public function __construct(
        public ?string $walletId,
    ) {
    }
}

final readonly class DayClosedForAggregateBackedTest
{
    public function __construct(
        #[EventTag('day')] public string $day,
    ) {
    }
}

#[EventSourcingAggregate]
#[AggregateType('WalletForAggregateBacked')]
final class WalletForAggregateBackedTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $walletId;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedTest $event): void
    {
        $this->walletId = $event->walletId;
    }

    #[Apply]
    public function debited(WalletDebitedForAggregateBackedTest $event): void
    {
        $this->walletId = $event->walletId;
    }

    #[Apply]
    public function frozen(WalletFrozenForAggregateBackedTest $event): void
    {
        $this->walletId = $event->walletId;
    }
}

#[EventSourcingAggregate]
#[AggregateType('LedgerForAggregateBacked')]
final class LedgerForAggregateBackedTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $ledger;

    #[Apply]
    public function credited(LedgerCreditedForAggregateBackedTest $event): void
    {
        $this->ledger = $event->ledgerId;
    }
}

#[DecisionModel(aggregate: WalletForAggregateBackedTest::class)]
final class WalletBalanceForAggregateBackedTest
{
    private int $balance = 0;

    /** @var int[] */
    private array $runningBalances = [];

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedTest $event): void
    {
        $this->balance += $event->amount;
        $this->runningBalances[] = $this->balance;
    }

    #[Apply]
    public function debited(WalletDebitedForAggregateBackedTest $event): void
    {
        $this->balance -= $event->amount;
        $this->runningBalances[] = $this->balance;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public function foldedEvents(): int
    {
        return count($this->runningBalances);
    }

    /**
     * @return int[]
     */
    public function runningBalances(): array
    {
        return $this->runningBalances;
    }

    public function canCover(int $amount): bool
    {
        return $this->balance >= $amount;
    }
}

#[DecisionModel(aggregate: WalletForAggregateBackedTest::class)]
final class WalletCreditCountForAggregateBackedTest
{
    private int $credits = 0;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedTest $event): void
    {
        $this->credits++;
    }

    public function credits(): int
    {
        return $this->credits;
    }
}

#[DecisionModel(aggregate: LedgerForAggregateBackedTest::class)]
final class LedgerBalanceForAggregateBackedTest
{
    private int $balance = 0;

    #[Apply]
    public function credited(LedgerCreditedForAggregateBackedTest $event): void
    {
        $this->balance += $event->amount;
    }

    public function balance(): int
    {
        return $this->balance;
    }
}

#[DecisionModel(tags: ['wallet'])]
final class PayoutsTodayForAggregateBackedTest
{
    private int $total = 0;

    #[Apply]
    public function requested(PayoutRequestedForAggregateBackedTest $event): void
    {
        $this->total += $event->amount;
    }

    public function total(): int
    {
        return $this->total;
    }
}

final class PayoutsForAggregateBackedTest
{
    /** @var array<string, mixed> */
    public static array $observed = [];

    #[CommandHandler]
    public function payOut(
        RequestPayoutForAggregateBackedTest $command,
        WalletBalanceForAggregateBackedTest $wallet,
        PayoutsTodayForAggregateBackedTest $today,
    ): array {
        self::$observed['balance'] = $wallet->balance();
        self::$observed['foldedEvents'] = $wallet->foldedEvents();
        self::$observed['runningBalances'] = $wallet->runningBalances();
        self::$observed['paidOutToday'] = $today->total();

        if (! $wallet->canCover($command->amount)) {
            throw new InsufficientFundsForAggregateBackedTest();
        }

        return [new PayoutRequestedForAggregateBackedTest($command->walletId, $command->amount)];
    }

    #[CommandHandler]
    public function transfer(
        TransferMoneyForAggregateBackedTest $command,
        #[Fetch('payload.fromWalletId')] WalletBalanceForAggregateBackedTest $from,
        #[Fetch('payload.toWalletId')] WalletBalanceForAggregateBackedTest $to,
    ): array {
        self::$observed['transfer'] = ['from' => $from->balance(), 'to' => $to->balance()];

        return [];
    }

    #[CommandHandler]
    public function auditWallet(
        AuditWalletForAggregateBackedTest $command,
        #[Fetch("{'walletId': payload.auditedWalletId}")] WalletBalanceForAggregateBackedTest $wallet,
    ): array {
        self::$observed['auditedBalance'] = $wallet->balance();

        return [];
    }

    #[CommandHandler]
    public function auditLedger(AuditLedgerForAggregateBackedTest $command, LedgerBalanceForAggregateBackedTest $ledger): array
    {
        self::$observed['ledgerBalance'] = $ledger->balance();

        return [];
    }

    #[CommandHandler]
    public function reviewWallet(
        ReviewWalletForAggregateBackedTest $command,
        WalletBalanceForAggregateBackedTest $wallet,
        WalletCreditCountForAggregateBackedTest $credits,
    ): array {
        self::$observed['review'] = ['balance' => $wallet->balance(), 'credits' => $credits->credits()];

        return [];
    }

    #[QueryHandler]
    public function checkBalance(CheckWalletBalanceForAggregateBackedTest $query, WalletBalanceForAggregateBackedTest $wallet): int
    {
        return $wallet->balance();
    }

    #[CommandHandler]
    public function closeDay(CloseDayForAggregateBackedTest $command, ?WalletBalanceForAggregateBackedTest $wallet): array
    {
        self::$observed['walletWasLoaded'] = $wallet !== null;

        return [new DayClosedForAggregateBackedTest('2026-09-29')];
    }
}
