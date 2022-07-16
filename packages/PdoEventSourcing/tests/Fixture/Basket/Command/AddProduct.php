<?php

namespace Test\Ecotone\EventSourcing\Fixture\Basket\Command;

class AddProduct
{
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
