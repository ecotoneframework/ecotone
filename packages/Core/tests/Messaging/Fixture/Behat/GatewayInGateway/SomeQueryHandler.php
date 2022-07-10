<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\QueryBus;

class SomeQueryHandler
{
    const SUM = "sum";
    const MULTIPLY = "multiply";
    const SUM_AND_MULTIPLY = "sumAndMultiply";
    const CALCULATE = "calculate";

    #[QueryHandler(SomeQueryHandler::CALCULATE)]
    public function calculate(int $sum, QueryBus $queryBus) : int
    {
        return $this->callQueryBus(self::SUM_AND_MULTIPLY, $queryBus, $sum);
    }

    #[QueryHandler(SomeQueryHandler::SUM)]
    public function sum(int $amount)
    {
        return $amount + 1;
    }

    #[QueryHandler(SomeQueryHandler::MULTIPLY)]
    public function multiply(int $amount)
    {
        return $amount * 2;
    }

    #[QueryHandler(SomeQueryHandler::SUM_AND_MULTIPLY)]
    public function sumAndMultiply(int $amount, QueryBus $queryBus)
    {
        $sum = $this->callQueryBus(self::SUM, $queryBus, $amount);
        return $this->callQueryBus(self::MULTIPLY, $queryBus, $sum);
    }

    private function callQueryBus(string $action, QueryBus $queryBus, int $sum)
    {
        return $queryBus->sendWithRouting($action, $sum);
    }
}