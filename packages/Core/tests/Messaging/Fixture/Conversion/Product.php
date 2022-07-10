<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion;


use Test\Ecotone\Messaging\Fixture\Conversion\Admin;

class Product
{
    private string $name;

    private ?int $quantity;
    /**
     * @var Admin[]
     */
    private array $owners;
}