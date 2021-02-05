<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptingAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Basket
{
    #[AggregateIdentifier]
    private string $userId;
    private array $items;

    private function __construct(string $personId, array $items)
    {
        $this->userId = $personId;
        $this->items  = $items;
    }

    #[CommandHandler("basket.add")]
    public static function start(array $command) : self
    {
        return new self($command["userId"], [$command["item"]]);
    }

    #[CommandHandler("basket.add")]
    public function addToBasket(array $command) : void
    {
        $this->items[] = $command["item"];
    }

    #[QueryHandler("basket.get")]
    public function getBasket() : array
    {
        return $this->items;
    }
}