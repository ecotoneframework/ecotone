<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\Causation;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use Ecotone\Api\Modelling\WithEvents;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateNotFoundCausationTest extends TestCase
{
    public function test_aggregate_not_found_names_the_handler_and_messages_that_led_to_the_command(): void
    {
        $order = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            public string $orderId;

            #[CommandHandler('order.create')]
            public static function create(array $command): self
            {
                $order = new self();
                $order->orderId = $command['orderId'];
                $order->recordThat(new OrderCreated($command['orderId'], $command['walletId']));

                return $order;
            }
        };
        $walletCharging = new class () {
            #[EventHandler]
            public function chargeWalletWhenOrderCreated(OrderCreated $event, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('wallet.charge', 100, metadata: ['aggregate.id' => $event->walletId]);
            }
        };
        $wallet = new #[Aggregate] class () {
            #[Identifier]
            public string $walletId;

            #[CommandHandler('wallet.charge')]
            public function charge(int $amount): void
            {
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$order::class, $walletCharging::class, $wallet::class],
            [$walletCharging],
            enabledModules: [],
        );

        $this->expectException(AggregateNotFoundException::class);
        $this->expectExceptionMessage('Aggregate ' . $wallet::class . ' for calling charge was not found using identifiers {"walletId":"wallet-404"}. Command wallet.charge was sent by ' . $walletCharging::class . '::chargeWalletWhenOrderCreated() while handling event ' . OrderCreated::class . ', which was published while handling command order.create.');

        $ecotone->sendCommandWithRouting('order.create', ['orderId' => 'order-1', 'walletId' => 'wallet-404']);
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class OrderCreated
{
    public function __construct(public string $orderId, public string $walletId)
    {
    }
}
