<?php


namespace Test\Ecotone\Modelling\Fixture\DistributedEventHandler;


use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class ShoppingRecord
{
    const COUNT_BOUGHT_GOODS = "countBoughtGoods";
    const ORDER_WAS_MADE     = "order.was_made";
    private array $boughtGoods = [];

    #[Distributed]
    #[EventHandler(self::ORDER_WAS_MADE)]
    public function register(string $order) : void
    {
        $this->boughtGoods[] = $order;
    }

    #[QueryHandler(self::COUNT_BOUGHT_GOODS)]
    public function countBoughtGood() : int
    {
        return count($this->boughtGoods);
    }
}