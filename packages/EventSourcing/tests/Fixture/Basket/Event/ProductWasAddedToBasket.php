<?php


namespace Test\Ecotone\EventSourcing\Fixture\Basket\Event;

use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::EVENT_NAME)]
class ProductWasAddedToBasket
{
    public const EVENT_NAME = "basket.product_was_added";

    private string $id;
    private string $productName;

    public function __construct(string $id, string $productName)
    {
        $this->id = $id;
        $this->productName = $productName;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }
}