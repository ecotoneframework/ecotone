<?php


namespace Test\Ecotone\EventSourcing\Fixture\Basket;


use Ecotone\Messaging\Attribute\Converter;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\BasketWasCreated;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\ProductWasAddedToBasket;

class BasketEventConverter
{
    #[Converter]
    public function fromBasketWasCreated(BasketWasCreated $event) : array
    {
        return [
            "id" => $event->getId()
        ];
    }

    #[Converter]
    public function toBasketWasCreated(array $event) : BasketWasCreated
    {
        return new BasketWasCreated($event["id"]);
    }

    #[Converter]
    public function fromProductWasAddedToBasket(ProductWasAddedToBasket $event) : array
    {
        return [
            "id" => $event->getId(),
            "productName" => $event->getProductName()
        ];
    }

    #[Converter]
    public function toProductWasAddedToBasket(array $event) : ProductWasAddedToBasket
    {
        return new ProductWasAddedToBasket($event["id"], $event["productName"]);
    }
}