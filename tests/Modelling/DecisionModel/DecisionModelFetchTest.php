<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
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
final class DecisionModelFetchTest extends TestCase
{
    public function test_fetch_maps_the_same_model_class_injected_twice_with_different_tag_values(): void
    {
        $handler = new TransferHandlerForFetchTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, AccountActivityForFetchTest::class, MoneyTransferredForFetchTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new MoneyTransferredForFetchTest('acc-1', 'acc-2', 10)]);

        $ecotone->sendCommand(new TransferMoneyForFetchTest('acc-1', 'acc-2', 5));

        $this->assertSame(['acc-1' => 1, 'acc-2' => 1], TransferHandlerForFetchTest::$observedCounts);
    }

    public function test_fetch_expression_resolving_a_map_scopes_a_multi_tag_model(): void
    {
        $handler = new RedeemCouponHandlerForFetchTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CustomerCouponUseForFetchTest::class, OrderPlacedForFetchTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new OrderPlacedForFetchTest('order-1', 'alice', 'SUMMER24')]);

        $ecotone->sendCommand(new RedeemCouponForFetchTest('alice', 'SUMMER24'));

        $this->assertTrue(RedeemCouponHandlerForFetchTest::$observedAlreadyUsed);
    }

    public function test_fetch_resolving_an_empty_value_is_rejected_naming_the_model_and_the_tag(): void
    {
        $handler = new CountingHandlerForFetchTest();
        $ecotone = $this->bootstrapCountingHandler($handler);

        try {
            $ecotone->sendCommand(new CountAccountActivityForFetchTest(''));
            $this->fail('Expected an ExpressionEvaluationException');
        } catch (ExpressionEvaluationException $exception) {
            $this->assertStringContainsString(AccountActivityForFetchTest::class, $exception->getMessage());
            $this->assertStringContainsString("'account'", $exception->getMessage());
            $this->assertStringContainsString('empty', $exception->getMessage());
        }
    }

    public function test_fetch_resolving_a_value_with_trailing_whitespace_is_rejected_like_an_event_tag_value(): void
    {
        $ecotone = $this->bootstrapCountingHandler(new CountingHandlerForFetchTest());

        $this->expectException(ExpressionEvaluationException::class);
        $this->expectExceptionMessage('trailing whitespace');

        $ecotone->sendCommand(new CountAccountActivityForFetchTest('acc-1 '));
    }

    public function test_fetch_resolving_an_integer_matches_the_same_integer_tagged_on_events(): void
    {
        $ecotone = $this->bootstrapCountingHandler(new CountingHandlerForFetchTest());

        $ecotone->withEvents([new MoneyTransferredForFetchTest('7', 'acc-2', 10)]);

        $ecotone->sendCommand(new CountAccountActivityForFetchTest(7));

        $this->assertSame(1, CountingHandlerForFetchTest::$observedCount);
    }

    public function test_fetch_resolving_a_list_of_values_for_a_single_tag_model_is_rejected_naming_model_and_tag(): void
    {
        $handler = new CountingSeveralAccountsHandlerForFetchTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, AccountActivityForFetchTest::class, MoneyTransferredForFetchTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        try {
            $ecotone->sendCommand(new CountSeveralAccountsForFetchTest(['acc-1', 'acc-2']));
            $this->fail('Expected an ExpressionEvaluationException');
        } catch (ExpressionEvaluationException $exception) {
            $this->assertStringContainsString(AccountActivityForFetchTest::class, $exception->getMessage());
            $this->assertStringContainsString("'account'", $exception->getMessage());
            $this->assertStringContainsString('one value', $exception->getMessage());
        }
    }

    private function bootstrapCountingHandler(CountingHandlerForFetchTest $handler)
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, AccountActivityForFetchTest::class, MoneyTransferredForFetchTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class RedeemCouponForFetchTest
{
    public function __construct(
        public string $customerId,
        public string $couponCode,
    ) {
    }
}

final readonly class OrderPlacedForFetchTest
{
    public function __construct(
        public string $orderId,
        #[EventTag('customer')] public string $customerId,
        #[EventTag('coupon')] public string $couponCode,
    ) {
    }
}

#[DecisionModel(tags: ['customer', 'coupon'])]
final class CustomerCouponUseForFetchTest
{
    private bool $used = false;

    #[Apply]
    public function redeemed(OrderPlacedForFetchTest $event): void
    {
        $this->used = true;
    }

    public function alreadyUsed(): bool
    {
        return $this->used;
    }
}

final class RedeemCouponHandlerForFetchTest
{
    public static bool $observedAlreadyUsed = false;

    #[CommandHandler]
    public function redeem(
        RedeemCouponForFetchTest $command,
        #[Fetch("{'customer': payload.customerId, 'coupon': payload.couponCode}")] CustomerCouponUseForFetchTest $usage,
    ): array {
        self::$observedAlreadyUsed = $usage->alreadyUsed();

        return [];
    }
}

final readonly class TransferMoneyForFetchTest
{
    public function __construct(
        public string $fromAccountId,
        public string $toAccountId,
        public int $amount,
    ) {
    }
}

final readonly class MoneyTransferredForFetchTest
{
    public function __construct(
        #[EventTag('account')] public string $fromAccountId,
        #[EventTag('account')] public string $toAccountId,
        public int $amount,
    ) {
    }
}

#[DecisionModel]
final class AccountActivityForFetchTest
{
    private int $transferCount = 0;

    #[Apply]
    public function transferred(MoneyTransferredForFetchTest $event): void
    {
        $this->transferCount++;
    }

    public function transferCount(): int
    {
        return $this->transferCount;
    }
}

final class TransferHandlerForFetchTest
{
    /** @var array<string, int> */
    public static array $observedCounts = [];

    #[CommandHandler]
    public function transfer(
        TransferMoneyForFetchTest $command,
        #[Fetch('payload.fromAccountId')] AccountActivityForFetchTest $from,
        #[Fetch('payload.toAccountId')] AccountActivityForFetchTest $to,
    ): array {
        self::$observedCounts = [
            $command->fromAccountId => $from->transferCount(),
            $command->toAccountId => $to->transferCount(),
        ];

        return [new MoneyTransferredForFetchTest($command->fromAccountId, $command->toAccountId, $command->amount)];
    }
}

final readonly class CountAccountActivityForFetchTest
{
    public function __construct(
        public string|int $reference,
    ) {
    }
}

final class CountingHandlerForFetchTest
{
    public static int $observedCount = 0;

    #[CommandHandler]
    public function count(CountAccountActivityForFetchTest $command, #[Fetch('payload.reference')] AccountActivityForFetchTest $account): array
    {
        self::$observedCount = $account->transferCount();

        return [];
    }
}

final readonly class CountSeveralAccountsForFetchTest
{
    /**
     * @param string[] $references
     */
    public function __construct(
        public array $references,
    ) {
    }
}

final class CountingSeveralAccountsHandlerForFetchTest
{
    #[CommandHandler]
    public function count(CountSeveralAccountsForFetchTest $command, #[Fetch('payload.references')] AccountActivityForFetchTest $account): array
    {
        return [];
    }
}
