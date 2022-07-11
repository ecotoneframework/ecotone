<?php

namespace Test\Ecotone\EventSourcing\Fixture\BasketListProjection;

use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\EventSourcing\Fixture\Basket\Basket;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\BasketWasCreated;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\ProductWasAddedToBasket;

#[Projection(self::PROJECTION_NAME, Basket::BASKET_STREAM)]
class BasketList
{
    public const PROJECTION_NAME = 'basketList';
    private array $basketsList = [];

    #[EventHandler(BasketWasCreated::EVENT_NAME)]
    public function addBasket(array $event): void
    {
        $this->basketsList[$event['id']] = [];
    }

    #[EventHandler(ProductWasAddedToBasket::EVENT_NAME)]
    public function addProduct(ProductWasAddedToBasket $event): void
    {
        $this->basketsList[$event->getId()][] = $event->getProductName();
    }

    #[QueryHandler('getALlBaskets')]
    public function getAllBaskets(): array
    {
        return $this->basketsList;
    }
}
