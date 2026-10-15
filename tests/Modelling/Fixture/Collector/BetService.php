<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class BetService
{
    private array $betHeaders = [];
    private bool $isFirstBet = true;

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
    public function asyncMakeBet(bool $shouldThrowException, #[Reference] EventBus $eventBus, #[Headers] array $headers): void
    {
        $this->betHeaders[] = $headers;
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
    public function when(BetPlaced $event, EventBus $eventBus, #[Headers] $headers): void
    {
        if ($this->isFirstBet) {
            $this->isFirstBet = false;

            $eventBus->publish(new BetWon());
        }

        $this->betHeaders[] = $headers;
    }

    #[Asynchronous('bets')]
    #[EventHandler(endpointId: 'whenBetWon')]
    public function whenBetWon(BetWon $event, #[Headers] $headers): void
    {
        $this->betHeaders[] = $headers;
    }

    #[QueryHandler('getLastBetHeaders')]
    public function getBetHeaders(): ?array
    {
        return array_shift($this->betHeaders);
    }
}
