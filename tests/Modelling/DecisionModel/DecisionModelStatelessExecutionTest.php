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
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

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
final class DecisionModelStatelessExecutionTest extends TestCase
{
    public function test_query_handler_dispatched_repeatedly_with_the_same_message_id_reflects_appends_made_between_dispatches(): void
    {
        $ecotone = $this->bootstrap([WalletQueryHandlerForStatelessTest::class], [new WalletQueryHandlerForStatelessTest()]);
        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 10)]);

        $balances = [];
        foreach ([5, 7, 11] as $amount) {
            $balances[] = $ecotone->sendQuery(new WalletBalanceForStatelessTest('wallet-1'), [MessageHeaders::MESSAGE_ID => 'same-query']);
            $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', $amount)]);
        }
        $balances[] = $ecotone->sendQuery(new WalletBalanceForStatelessTest('wallet-1'), [MessageHeaders::MESSAGE_ID => 'same-query']);

        self::assertSame([10, 15, 22, 33], $balances);
    }

    public function test_command_redispatched_with_the_same_message_id_after_its_handler_failed_sees_state_written_in_between(): void
    {
        $handler = new WalletCommandHandlerForStatelessTest();
        $ecotone = $this->bootstrap([WalletCommandHandlerForStatelessTest::class], [$handler]);
        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 10)]);

        $this->expectFailure(fn () => $ecotone->sendCommand(new SpendFromWalletForStatelessTest('wallet-1', 4, failAfterDeciding: true), [MessageHeaders::MESSAGE_ID => 'same-command']));

        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 5)]);
        $ecotone->sendCommand(new SpendFromWalletForStatelessTest('wallet-1', 4, failAfterDeciding: false), [MessageHeaders::MESSAGE_ID => 'same-command']);

        self::assertSame([15], $handler->balancesSeen);
    }

    public function test_command_redispatched_with_the_same_message_id_after_argument_resolution_failed_sees_state_written_in_between(): void
    {
        $handler = new AuditedWalletCommandHandlerForStatelessTest();
        $ecotone = $this->bootstrap([AuditedWalletCommandHandlerForStatelessTest::class], [$handler]);
        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 10)]);

        $this->expectFailure(fn () => $ecotone->sendCommand(new AuditWalletForStatelessTest('wallet-1'), [MessageHeaders::MESSAGE_ID => 'same-command']));

        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 5)]);
        $ecotone->sendCommand(new AuditWalletForStatelessTest('wallet-1'), [MessageHeaders::MESSAGE_ID => 'same-command', 'auditor' => 'alice']);

        self::assertSame([[15, 15]], $handler->balancesSeen);
    }

    public function test_aggregate_command_retried_with_the_same_message_id_after_its_handler_failed_appends_with_its_own_condition_only(): void
    {
        $ecotone = $this->bootstrap([PaymentForStatelessTest::class, CompetingFundingForStatelessTest::class], [new CompetingFundingForStatelessTest()]);
        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 10)]);

        $this->expectFailure(fn () => $ecotone->sendCommand(new MakePaymentForStatelessTest('payment-1', 'wallet-1', 50), [MessageHeaders::MESSAGE_ID => 'same-payment']));

        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 5)]);
        $ecotone->sendCommand(new MakePaymentForStatelessTest('payment-1', null, 50), [MessageHeaders::MESSAGE_ID => 'same-payment']);

        self::assertCount(1, $ecotone->getGateway(EventStore::class)->loadByCriteria(EventCriteria::tag('payment', 'payment-1'))->events);
    }

    public function test_aggregate_command_retried_with_the_same_message_id_still_detects_a_conflict_on_its_own_tags(): void
    {
        $ecotone = $this->bootstrap([PaymentForStatelessTest::class, CompetingFundingForStatelessTest::class], [new CompetingFundingForStatelessTest()]);
        $ecotone->withEvents([new WalletFundedForStatelessTest('wallet-1', 100)]);

        $this->expectFailure(fn () => $ecotone->sendCommand(new MakePaymentForStatelessTest('payment-1', 'wallet-1', 500), [MessageHeaders::MESSAGE_ID => 'same-payment']));

        $ecotone->getServiceFromContainer(CompetingFundingForStatelessTest::class)->arm();

        $this->expectFailure(fn () => $ecotone->sendCommand(new MakePaymentForStatelessTest('payment-1', 'wallet-1', 50), [MessageHeaders::MESSAGE_ID => 'same-payment']));

        self::assertCount(0, $ecotone->getGateway(EventStore::class)->loadByCriteria(EventCriteria::tag('payment', 'payment-1'))->events);
    }

    private function expectFailure(callable $action): void
    {
        try {
            $action();
        } catch (Throwable) {
            return;
        }

        self::fail('Expected the dispatch to fail');
    }

    private function bootstrap(array $classes, array $services): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [...$classes, WalletForStatelessTest::class, WalletFundedForStatelessTest::class, WalletChargedForStatelessTest::class, PaymentMadeForStatelessTest::class],
            containerOrAvailableServices: $services,
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class WalletFundedForStatelessTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletChargedForStatelessTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class SpendFromWalletForStatelessTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
        public bool $failAfterDeciding,
    ) {
    }
}

final readonly class WalletBalanceForStatelessTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class AuditWalletForStatelessTest
{
    public function __construct(
        public string $walletId,
    ) {
    }
}

final readonly class MakePaymentForStatelessTest
{
    public function __construct(
        public string $paymentId,
        #[EventTag('wallet')] public ?string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class PaymentMadeForStatelessTest
{
    public function __construct(
        #[EventTag('payment')] public string $paymentId,
        public int $amount,
    ) {
    }
}

#[DecisionModel]
final class WalletForStatelessTest
{
    private int $balance = 0;

    #[Apply]
    public function funded(WalletFundedForStatelessTest $event): void
    {
        $this->balance += $event->amount;
    }

    #[Apply]
    public function charged(WalletChargedForStatelessTest $event): void
    {
        $this->balance -= $event->amount;
    }

    public function balance(): int
    {
        return $this->balance;
    }
}

final class WalletQueryHandlerForStatelessTest
{
    #[QueryHandler]
    public function balance(WalletBalanceForStatelessTest $query, WalletForStatelessTest $wallet): int
    {
        return $wallet->balance();
    }
}

final class WalletCommandHandlerForStatelessTest
{
    /** @var int[] */
    public array $balancesSeen = [];

    #[CommandHandler]
    public function spend(SpendFromWalletForStatelessTest $command, WalletForStatelessTest $wallet): array
    {
        if ($command->failAfterDeciding) {
            throw new RuntimeException('Failure after the decision model was loaded');
        }

        $this->balancesSeen[] = $wallet->balance();

        return [new WalletChargedForStatelessTest($command->walletId, $command->amount)];
    }
}

final class AuditedWalletCommandHandlerForStatelessTest
{
    /** @var array<array{int, int}> */
    public array $balancesSeen = [];

    #[CommandHandler]
    public function audit(
        AuditWalletForStatelessTest $command,
        #[Fetch('payload.walletId')] WalletForStatelessTest $wallet,
        #[Header('auditor')] string $auditor,
        #[Fetch('payload.walletId')] WalletForStatelessTest $sameWallet,
    ): void {
        $this->balancesSeen[] = [$wallet->balance(), $sameWallet->balance()];
    }
}

final class CompetingFundingForStatelessTest
{
    private bool $armed = false;

    public function arm(): void
    {
        $this->armed = true;
    }

    public function maybeFund(EventStore $eventStore): void
    {
        if (! $this->armed) {
            return;
        }

        $this->armed = false;
        $eventStore->appendTo('ecotone_event_stream', [new WalletFundedForStatelessTest('wallet-1', 1)]);
    }
}

#[EventSourcingAggregate]
#[AggregateType('PaymentForStateless')]
final class PaymentForStatelessTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $paymentId;

    #[CommandHandler]
    public static function make(MakePaymentForStatelessTest $command, ?WalletForStatelessTest $wallet, #[Reference] CompetingFundingForStatelessTest $competingFunding, #[Reference] EventStore $eventStore): array
    {
        if ($wallet !== null && $wallet->balance() < $command->amount) {
            throw new RuntimeException('Insufficient funds');
        }

        $competingFunding->maybeFund($eventStore);

        return [new PaymentMadeForStatelessTest($command->paymentId, $command->amount)];
    }

    #[Apply]
    public function whenMade(PaymentMadeForStatelessTest $event): void
    {
        $this->paymentId = $event->paymentId;
    }
}
