<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\AggregateBoundary;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Attribute\Version;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
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
final class StateStoredAggregateCounterTest extends TestCase
{
    public function test_two_concurrent_commands_on_one_state_stored_aggregate_let_one_succeed_and_raise_on_the_other_naming_the_aggregate(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('purse.open', 'p-1');

        try {
            $ecotone->sendCommandWithRouting('purse.withdrawWhileAnotherWithdrawalLands', ['purseId' => 'p-1', 'amount' => 30]);
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('Purse p-1 changed since it was loaded', $exception->getMessage());
        }

        $this->assertSame(2, $this->counterOf($ecotone, 'p-1'));
    }

    public function test_sequential_commands_on_a_state_stored_aggregate_each_move_its_counter_and_leave_its_own_version_untouched(): void
    {
        $ecotone = $this->bootstrapWithDcb();

        $ecotone->sendCommandWithRouting('purse.open', 'p-1');
        $ecotone->sendCommandWithRouting('purse.withdraw', ['purseId' => 'p-1', 'amount' => 30]);
        $ecotone->sendCommandWithRouting('purse.withdraw', ['purseId' => 'p-1', 'amount' => 20]);

        $this->assertSame(3, $this->counterOf($ecotone, 'p-1'));
        $this->assertSame(50, $ecotone->sendQueryWithRouting('purse.balance', metadata: ['aggregate.id' => 'p-1']));
        $this->assertSame(
            $this->versionAfterSameCommandsWithoutDcb(),
            $ecotone->sendQueryWithRouting('purse.version', metadata: ['aggregate.id' => 'p-1']),
        );
    }

    public function test_a_decision_on_a_captured_state_stored_aggregate_counter_fails_after_the_aggregate_is_saved(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('purse.open', 'p-1');

        $captured = $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_Purse', 'p-1'))->appendCondition;
        $ecotone->sendCommandWithRouting('purse.withdraw', ['purseId' => 'p-1', 'amount' => 30]);

        $this->expectException(DecisionModelConcurrencyException::class);

        $this->eventStore($ecotone)->appendTo('ecotone_event_stream', [new PurseAudited('p-1')], $captured);
    }

    public function test_concurrent_commands_on_a_state_stored_aggregate_keep_last_write_wins_when_dcb_is_disabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(classesToResolve: [Purse::class, PurseAudited::class], enabledModules: []);
        $ecotone->sendCommandWithRouting('purse.open', 'p-1');

        $ecotone->sendCommandWithRouting('purse.withdrawWhileAnotherWithdrawalLands', ['purseId' => 'p-1', 'amount' => 30]);

        $this->assertSame(20, $ecotone->sendQueryWithRouting('purse.balance', metadata: ['aggregate.id' => 'p-1']));
    }

    public function test_concurrent_commands_on_a_state_stored_saga_are_not_guarded(): void
    {
        $ecotone = $this->bootstrapWithDcb();
        $ecotone->sendCommandWithRouting('refund.start', 'r-1');

        $ecotone->sendCommandWithRouting('refund.approveWhileAnotherApprovalLands', ['refundId' => 'r-1']);

        $this->assertSame(2, $ecotone->sendQueryWithRouting('refund.approvals', metadata: ['aggregate.id' => 'r-1']));
    }

    private function versionAfterSameCommandsWithoutDcb(): int
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(classesToResolve: [Purse::class, PurseAudited::class], enabledModules: []);
        $ecotone->sendCommandWithRouting('purse.open', 'p-1');
        $ecotone->sendCommandWithRouting('purse.withdraw', ['purseId' => 'p-1', 'amount' => 30]);
        $ecotone->sendCommandWithRouting('purse.withdraw', ['purseId' => 'p-1', 'amount' => 20]);

        return $ecotone->sendQueryWithRouting('purse.version', metadata: ['aggregate.id' => 'p-1']);
    }

    private function counterOf(FlowTestSupport $ecotone, string $purseId): int
    {
        return $this->eventStore($ecotone)->loadByCriteria(EventCriteria::tag('aggregate_Purse', $purseId))->appendCondition->expectedTagVersions()[0]['expectedVersion'];
    }

    private function eventStore(FlowTestSupport $ecotone): EventStore
    {
        return $ecotone->getGateway(EventStore::class);
    }

    private function bootstrapWithDcb(): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Purse::class, PurseAudited::class, RefundSaga::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[Aggregate]
#[AggregateType('Purse')]
final class Purse
{
    #[Version]
    private int $version = 0;

    private function __construct(#[Identifier] private string $purseId, private int $balance)
    {
    }

    #[CommandHandler('purse.open')]
    public static function open(string $purseId): self
    {
        return new self($purseId, 100);
    }

    #[CommandHandler('purse.withdraw')]
    public function withdraw(array $command): void
    {
        $this->balance -= $command['amount'];
    }

    #[CommandHandler('purse.withdrawWhileAnotherWithdrawalLands')]
    public function withdrawWhileAnotherWithdrawalLands(array $command, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('purse.withdraw', ['purseId' => $this->purseId, 'amount' => 50]);

        $this->balance -= $command['amount'];
    }

    #[QueryHandler('purse.balance')]
    public function balance(): int
    {
        return $this->balance;
    }

    #[QueryHandler('purse.version')]
    public function version(): int
    {
        return $this->version;
    }
}

final readonly class PurseAudited
{
    public function __construct(public string $purseId)
    {
    }
}

#[Saga]
final class RefundSaga
{
    private int $approvals = 0;

    private function __construct(#[Identifier] private string $refundId)
    {
    }

    #[CommandHandler('refund.start')]
    public static function start(string $refundId): self
    {
        return new self($refundId);
    }

    #[CommandHandler('refund.approve')]
    public function approve(array $command): void
    {
        $this->approvals++;
    }

    #[CommandHandler('refund.approveWhileAnotherApprovalLands')]
    public function approveWhileAnotherApprovalLands(array $command, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('refund.approve', ['refundId' => $this->refundId]);

        $this->approvals++;
    }

    #[QueryHandler('refund.approvals')]
    public function approvals(): int
    {
        return $this->approvals;
    }
}
