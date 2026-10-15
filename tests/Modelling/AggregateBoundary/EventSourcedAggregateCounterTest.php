<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\AggregateBoundary;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ConcurrencyException;
use Ecotone\Api\Modelling\WithAggregateVersioning;
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
final class EventSourcedAggregateCounterTest extends TestCase
{
    public function test_every_save_of_an_event_sourced_aggregate_moves_its_counter_tag_and_the_tag_reads_no_events(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');
        $ecotone->sendCommandWithRouting('ledgerWallet.deposit', ['walletId' => 'w-1', 'amount' => 50]);

        $loaded = $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerWallet', 'w-1'));

        $this->assertSame([], $loaded->events);
        $this->assertSame(2, $loaded->appendCondition->expectedTagVersions()[0]['expectedVersion']);
    }

    public function test_a_decision_on_a_captured_aggregate_counter_appends_when_the_aggregate_did_not_change(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');

        $captured = $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerWallet', 'w-1'))->appendCondition;
        $this->eventStore($ecotone)->appendTo('ecotone_event_stream', [new LedgerWalletAudited('w-1')], $captured);

        $this->assertSame(2, $this->counterOf($ecotone, 'w-1'));
    }

    public function test_a_decision_on_a_captured_aggregate_counter_fails_after_the_aggregate_is_saved_and_the_conflict_names_the_aggregate(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');

        $captured = $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerWallet', 'w-1'))->appendCondition;
        $ecotone->sendCommandWithRouting('ledgerWallet.deposit', ['walletId' => 'w-1', 'amount' => 50]);

        try {
            $this->eventStore($ecotone)->appendTo('ecotone_event_stream', [new LedgerWalletAudited('w-1')], $captured);
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('LedgerWallet w-1 changed since it was loaded', $exception->getMessage());
            $this->assertStringNotContainsString('aggregate_LedgerWallet', $exception->getMessage());
        }
    }

    public function test_saving_one_aggregate_does_not_move_the_counter_of_another_instance(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');
        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-2');

        $captured = $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerWallet', 'w-1'))->appendCondition;
        $ecotone->sendCommandWithRouting('ledgerWallet.deposit', ['walletId' => 'w-2', 'amount' => 50]);
        $this->eventStore($ecotone)->appendTo('ecotone_event_stream', [new LedgerWalletAudited('w-1')], $captured);

        $this->assertSame(2, $this->counterOf($ecotone, 'w-1'));
        $this->assertSame(2, $this->counterOf($ecotone, 'w-2'));
    }

    public function test_a_competing_save_of_the_same_aggregate_during_its_command_fails_the_outer_save(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');

        $this->expectException(ConcurrencyException::class);

        $ecotone->sendCommandWithRouting('ledgerWallet.depositWhileAnotherDepositLands', ['walletId' => 'w-1', 'amount' => 50]);
    }

    public function test_an_event_sourced_saga_save_moves_no_counter(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommandWithRouting('ledgerShipment.start', 's-1');

        $this->assertSame(0, $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerShipment', 's-1'))->appendCondition->expectedTagVersions()[0]['expectedVersion']);
    }

    public function test_an_event_sourced_aggregate_declaring_aggregate_type_is_reloaded_in_flow_testing_without_dcb(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [LedgerWallet::class, LedgerWalletOpened::class, LedgerWalletDeposited::class],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting('ledgerWallet.open', 'w-1');
        $ecotone->sendCommandWithRouting('ledgerWallet.deposit', ['walletId' => 'w-1', 'amount' => 50]);

        $this->assertSame(2, $ecotone->getAggregate(LedgerWallet::class, 'w-1')->getVersion());
    }

    private function counterOf(FlowTestSupport $ecotone, string $walletId): int
    {
        return $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_LedgerWallet', $walletId))->appendCondition->expectedTagVersions()[0]['expectedVersion'];
    }

    private function eventStore(FlowTestSupport $ecotone): EventStore
    {
        return $ecotone->getGateway(EventStore::class);
    }

    private function bootstrap(): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [LedgerWallet::class, LedgerWalletOpened::class, LedgerWalletDeposited::class, LedgerWalletAudited::class, LedgerShipmentSaga::class, LedgerShipmentStarted::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[EventSourcingAggregate]
#[AggregateType('LedgerWallet')]
final class LedgerWallet
{
    use WithAggregateVersioning;

    #[Identifier] private string $walletId;

    #[CommandHandler('ledgerWallet.open')]
    public static function open(string $walletId): array
    {
        return [new LedgerWalletOpened($walletId)];
    }

    #[CommandHandler('ledgerWallet.deposit')]
    public function deposit(array $command): array
    {
        return [new LedgerWalletDeposited($this->walletId, $command['amount'])];
    }

    #[CommandHandler('ledgerWallet.depositWhileAnotherDepositLands')]
    public function depositWhileAnotherDepositLands(array $command, CommandBus $commandBus): array
    {
        $commandBus->sendWithRouting('ledgerWallet.deposit', $command);

        return [new LedgerWalletDeposited($this->walletId, $command['amount'])];
    }

    #[Apply]
    public function applyOpened(LedgerWalletOpened $event): void
    {
        $this->walletId = $event->walletId;
    }
}

final readonly class LedgerWalletOpened
{
    public function __construct(public string $walletId)
    {
    }
}

final readonly class LedgerWalletDeposited
{
    public function __construct(public string $walletId, public int $amount)
    {
    }
}

final readonly class LedgerWalletAudited
{
    public function __construct(public string $walletId)
    {
    }
}

#[EventSourcingSaga]
#[AggregateType('LedgerShipment')]
final class LedgerShipmentSaga
{
    use WithAggregateVersioning;

    #[Identifier] private string $shipmentId;

    #[CommandHandler('ledgerShipment.start')]
    public static function start(string $shipmentId): array
    {
        return [new LedgerShipmentStarted($shipmentId)];
    }

    #[Apply]
    public function applyStarted(LedgerShipmentStarted $event): void
    {
        $this->shipmentId = $event->shipmentId;
    }
}

final readonly class LedgerShipmentStarted
{
    public function __construct(public string $shipmentId)
    {
    }
}
