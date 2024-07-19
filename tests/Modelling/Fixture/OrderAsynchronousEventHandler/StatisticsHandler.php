<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;

/**
 * licence Apache-2.0
 */
final class StatisticsHandler
{
    private array $statistics = [];

    #[EventHandler]
    public function handle(OrderWasPlaced $event, CommandBus $commandBus): void
    {
        $commandBus->send(new PushStatistics('1'));
        $commandBus->send(new PushStatistics('2'));
    }

    #[Asynchronous('pushStatistics')]
    #[CommandHandler(endpointId: 'StatisticsHandler::handleStatistics')]
    public function handleStatistics(PushStatistics $command): void
    {
        $this->statistics[] = $command->getId();
    }
}
