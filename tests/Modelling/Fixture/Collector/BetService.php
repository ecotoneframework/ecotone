<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use RuntimeException;

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
