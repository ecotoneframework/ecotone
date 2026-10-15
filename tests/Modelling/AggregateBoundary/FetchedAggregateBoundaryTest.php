<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\AggregateBoundary;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
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
final class FetchedAggregateBoundaryTest extends TestCase
{
    public function test_a_dcb_handler_fetching_an_event_sourced_aggregate_fails_its_append_when_the_aggregate_is_saved_during_the_decision(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);

        try {
            $ecotone->sendCommand(new PlaceOrderOnCredit('o-1', 'c-1', 'SUMMER24', 60, spendCompetingCredit: true));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('Customer c-1 changed since it was loaded', $exception->getMessage());
        }

        $this->assertSame(0, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_a_dcb_handler_fetching_an_event_sourced_aggregate_appends_when_nobody_saved_it(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);

        $ecotone->sendCommand(new PlaceOrderOnCredit('o-1', 'c-1', 'SUMMER24', 60));

        $this->assertSame(1, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_a_dcb_handler_fetching_a_state_stored_aggregate_fails_its_append_when_the_aggregate_is_saved_during_the_decision(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('wallet.open', 'w-1');

        try {
            $ecotone->sendCommand(new RequestPayout('w-1', 'SUMMER24', 60, withdrawCompetingAmount: true));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('Wallet w-1 changed since it was loaded', $exception->getMessage());
        }

        $this->assertSame(0, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_two_fetched_aggregates_both_guard_the_append_and_opposite_parameter_order_still_commits(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);
        $ecotone->sendCommandWithRouting('wallet.open', 'w-1');

        $ecotone->sendCommand(new SettleFromBoth('s-1', 'c-1', 'w-1', 'SUMMER24'));
        $ecotone->sendCommand(new SettleFromBothReversed('s-2', 'c-1', 'w-1', 'SUMMER24'));

        try {
            $ecotone->sendCommand(new SettleFromBoth('s-3', 'c-1', 'w-1', 'SUMMER24', withdrawCompetingAmount: true));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('Wallet w-1 changed since it was loaded', $exception->getMessage());
        }

        $this->assertSame(2, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_deciding_on_the_absence_of_an_aggregate_fails_when_it_is_created_during_the_decision(): void
    {
        $ecotone = $this->bootstrapWithDcb();

        try {
            $ecotone->sendCommand(new ClaimUnusedWallet('w-9', 'SUMMER24', openTheWalletMeanwhile: true));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('Wallet w-9 changed since it was loaded', $exception->getMessage());
        }
    }

    public function test_a_fetch_expression_resolving_to_no_identifier_contributes_nothing_and_the_handler_still_appends(): void
    {
        $ecotone = $this->bootstrapWithDcb();

        $ecotone->sendCommand(new ClaimUnusedWallet(null, 'SUMMER24'));

        $this->assertSame(1, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_a_decision_boundary_naming_only_an_aggregate_guards_the_append_on_that_aggregate(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);

        $ecotone->sendCommand(new ReserveCredit('c-1', 10));

        $this->expectException(DecisionModelConcurrencyException::class);

        $ecotone->sendCommand(new ReserveCredit('c-1', 10, spendCompetingCredit: true));
    }

    public function test_a_fetch_handler_that_is_not_a_dcb_handler_replies_with_its_result_and_appends_nothing(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);

        $reply = $ecotone->getCommandBus()->send(new QuoteOrder('c-1', 'SUMMER24'));

        $this->assertEquals([new CreditOrderPlaced('quote', 'SUMMER24')], $reply);
        $this->assertSame(0, $this->redemptionsOf($ecotone, 'SUMMER24'));
    }

    public function test_a_fetch_handler_behaves_as_today_when_dcb_is_disabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [CustomerOnCredit::class, CreditGranted::class, CreditSpent::class, QuoteService::class, CreditOrderPlaced::class],
            containerOrAvailableServices: [new QuoteService()],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
        $ecotone->sendCommandWithRouting('customer.grant', ['customerId' => 'c-1', 'amount' => 100]);

        $this->assertEquals([new CreditOrderPlaced('quote', 'SUMMER24')], $ecotone->getCommandBus()->send(new QuoteOrder('c-1', 'SUMMER24')));
    }

    public function test_a_saga_fetched_into_a_dcb_handler_is_rejected_at_bootstrap(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [SagaFetchingService::class, EscalationSaga::class, CouponRedemptions::class, CreditOrderPlaced::class, EscalateCase::class],
                containerOrAvailableServices: [new SagaFetchingService()],
                configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                    ->withEnabledModulePackageNames([]),
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(EscalationSaga::class, $exception->getMessage());
            $this->assertStringContainsString(SagaFetchingService::class . '::escalate', $exception->getMessage());
        }
    }

    private function redemptionsOf(FlowTestSupport $ecotone, string $couponId): int
    {
        return count($ecotone->getGateway(EventStore::class)->loadByCriteria(EventCriteria::tag('coupon', $couponId))->events);
    }

    private function bootstrapWithDcb(): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                CustomerOnCredit::class, CreditGranted::class, CreditSpent::class,
                Wallet::class,
                CouponRedemptions::class, CreditOrderPlaced::class,
                OrderOnCreditService::class, SettlementService::class, CreditReservationService::class, QuoteService::class,
            ],
            containerOrAvailableServices: [new OrderOnCreditService(), new SettlementService(), new CreditReservationService(), new QuoteService()],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[EventSourcingAggregate]
#[AggregateType('Customer')]
final class CustomerOnCredit
{
    use WithAggregateVersioning;

    #[Identifier] private string $customerId;
    private int $credit = 0;

    #[CommandHandler('customer.grant')]
    public static function grantFirst(array $command): array
    {
        return [new CreditGranted($command['customerId'], $command['amount'])];
    }

    #[CommandHandler('customer.spend')]
    public function spend(array $command): array
    {
        return [new CreditSpent($this->customerId, $command['amount'])];
    }

    #[Apply]
    public function applyGranted(CreditGranted $event): void
    {
        $this->customerId = $event->customerId;
        $this->credit += $event->amount;
    }

    #[Apply]
    public function applySpent(CreditSpent $event): void
    {
        $this->credit -= $event->amount;
    }

    public function availableCredit(): int
    {
        return $this->credit;
    }
}

final readonly class CreditGranted
{
    public function __construct(public string $customerId, public int $amount)
    {
    }
}

final readonly class CreditSpent
{
    public function __construct(public string $customerId, public int $amount)
    {
    }
}

#[Aggregate]
#[AggregateType('Wallet')]
final class Wallet
{
    private function __construct(#[Identifier] private string $walletId, private int $balance)
    {
    }

    #[CommandHandler('wallet.open')]
    public static function open(string $walletId): self
    {
        return new self($walletId, 100);
    }

    #[CommandHandler('wallet.withdraw')]
    public function withdraw(array $command): void
    {
        $this->balance -= $command['amount'];
    }

    public function balance(): int
    {
        return $this->balance;
    }
}

#[DecisionModel]
final class CouponRedemptions
{
    private int $redemptions = 0;

    #[Apply]
    public function whenPlaced(CreditOrderPlaced $event): void
    {
        $this->redemptions++;
    }

    public function redemptions(): int
    {
        return $this->redemptions;
    }
}

final readonly class CreditOrderPlaced
{
    public function __construct(public string $orderId, #[EventTag('coupon')] public string $couponId)
    {
    }
}

final readonly class PlaceOrderOnCredit
{
    public function __construct(public string $orderId, public string $customerId, public string $couponId, public int $amount, public bool $spendCompetingCredit = false)
    {
    }
}

final readonly class RequestPayout
{
    public function __construct(public string $walletId, public string $couponId, public int $amount, public bool $withdrawCompetingAmount = false)
    {
    }
}

final readonly class SettleFromBoth
{
    public function __construct(public string $settlementId, public string $customerId, public string $walletId, public string $couponId, public bool $withdrawCompetingAmount = false)
    {
    }
}

final readonly class SettleFromBothReversed
{
    public function __construct(public string $settlementId, public string $customerId, public string $walletId, public string $couponId)
    {
    }
}

final readonly class ClaimUnusedWallet
{
    public function __construct(public ?string $walletId, public string $couponId, public bool $openTheWalletMeanwhile = false)
    {
    }
}

final readonly class ReserveCredit
{
    public function __construct(public string $customerId, public int $amount, public bool $spendCompetingCredit = false)
    {
    }
}

final readonly class QuoteOrder
{
    public function __construct(public string $customerId, public string $couponId)
    {
    }
}

final class OrderOnCreditService
{
    #[CommandHandler]
    public function place(
        PlaceOrderOnCredit $command,
        #[Fetch('payload.customerId')] CustomerOnCredit $customer,
        CouponRedemptions $coupon,
        #[Reference] CommandBus $commandBus,
    ): array {
        $canPlace = $customer->availableCredit() >= $command->amount;
        if ($command->spendCompetingCredit) {
            $commandBus->sendWithRouting('customer.spend', ['customerId' => $command->customerId, 'amount' => 80]);
        }

        return $canPlace ? [new CreditOrderPlaced($command->orderId, $command->couponId)] : [];
    }

    #[CommandHandler]
    public function payOut(
        RequestPayout $command,
        #[Fetch('payload.walletId')] Wallet $wallet,
        CouponRedemptions $coupon,
        #[Reference] CommandBus $commandBus,
    ): array {
        $canPayOut = $wallet->balance() >= $command->amount;
        if ($command->withdrawCompetingAmount) {
            $commandBus->sendWithRouting('wallet.withdraw', ['walletId' => $command->walletId, 'amount' => 80]);
        }

        return $canPayOut ? [new CreditOrderPlaced($command->walletId, $command->couponId)] : [];
    }

    #[CommandHandler]
    public function claimUnusedWallet(
        ClaimUnusedWallet $command,
        #[Fetch('payload.walletId')] ?Wallet $wallet,
        CouponRedemptions $coupon,
        #[Reference] CommandBus $commandBus,
    ): array {
        if ($command->openTheWalletMeanwhile) {
            $commandBus->sendWithRouting('wallet.open', $command->walletId);
        }

        return $wallet === null ? [new CreditOrderPlaced('claim', $command->couponId)] : [];
    }
}

final class SettlementService
{
    #[CommandHandler]
    public function settle(
        SettleFromBoth $command,
        #[Fetch('payload.customerId')] CustomerOnCredit $customer,
        #[Fetch('payload.walletId')] Wallet $wallet,
        CouponRedemptions $coupon,
        #[Reference] CommandBus $commandBus,
    ): array {
        if ($command->withdrawCompetingAmount) {
            $commandBus->sendWithRouting('wallet.withdraw', ['walletId' => $command->walletId, 'amount' => 10]);
        }

        return [new CreditOrderPlaced($command->settlementId, $command->couponId)];
    }

    #[CommandHandler]
    public function settleReversed(
        SettleFromBothReversed $command,
        #[Fetch('payload.walletId')] Wallet $wallet,
        #[Fetch('payload.customerId')] CustomerOnCredit $customer,
        CouponRedemptions $coupon,
    ): array {
        return [new CreditOrderPlaced($command->settlementId, $command->couponId)];
    }
}

final class CreditReservationService
{
    #[DecisionBoundary]
    public static function boundary(ReserveCredit $command): EventCriteria
    {
        return EventCriteria::aggregate(CustomerOnCredit::class, $command->customerId);
    }

    #[CommandHandler]
    public function reserve(
        ReserveCredit $command,
        #[Fetch('payload.customerId')] CustomerOnCredit $customer,
        #[Reference] CommandBus $commandBus,
    ): array {
        if ($command->spendCompetingCredit) {
            $commandBus->sendWithRouting('customer.spend', ['customerId' => $command->customerId, 'amount' => 5]);
        }

        return [new CreditOrderPlaced('reservation', 'RESERVATIONS')];
    }
}

final class QuoteService
{
    #[CommandHandler]
    public function quote(QuoteOrder $command, #[Fetch('payload.customerId')] CustomerOnCredit $customer): array
    {
        return [new CreditOrderPlaced('quote', $command->couponId)];
    }
}

#[Saga]
final class EscalationSaga
{
    private function __construct(#[Identifier] private string $escalationId)
    {
    }

    #[CommandHandler('escalation.start')]
    public static function start(string $escalationId): self
    {
        return new self($escalationId);
    }
}

final readonly class EscalateCase
{
    public function __construct(public string $escalationId, public string $couponId)
    {
    }
}

final class SagaFetchingService
{
    #[CommandHandler]
    public function escalate(EscalateCase $command, #[Fetch('payload.escalationId')] EscalationSaga $saga, CouponRedemptions $coupon): array
    {
        return [];
    }
}
