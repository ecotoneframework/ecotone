<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use RuntimeException;

final class BetService
{
    #[CommandHandler('makeBet')]
    public function makeBet(bool $shouldThrowException, #[Reference] EventBus $eventBus): void
    {
        $eventBus->publish(new BetPlaced());

        if ($shouldThrowException) {
            throw new RuntimeException('test');
        }
    }

    #[Asynchronous('bets')]
    #[CommandHandler('asyncMakeBet', endpointId: 'asyncMakeBetEndpoint')]
    public function asyncMakeBet(bool $shouldThrowException, #[Reference] EventBus $eventBus): void
    {
        $eventBus->publish(new BetPlaced());

        if ($shouldThrowException) {
            throw new RuntimeException('test');
        }
    }

    #[CommandHandler('makeBlindBet')]
    public function makeBlindBet(bool $shouldThrowException, #[Reference] CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('makeBet', false);

        if ($shouldThrowException) {
            throw new RuntimeException('test');
        }
    }

    #[Asynchronous('bets')]
    #[EventHandler(endpointId: 'whenBetPlaced')]
    public function when(BetPlaced $event): void
    {

    }
}
