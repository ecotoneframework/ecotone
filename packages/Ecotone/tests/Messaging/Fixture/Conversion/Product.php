<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

class Product
{
    private string $name;

    private ?int $quantity;
    /**
     * @var Admin[]
     */
    private array $owners;
}
