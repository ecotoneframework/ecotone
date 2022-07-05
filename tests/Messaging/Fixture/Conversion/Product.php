<?php


namespace Tests\Ecotone\Messaging\Fixture\Conversion;


use Tests\Ecotone\Messaging\Fixture\Conversion\Admin;

class Product
{
    private string $name;

    private ?int $quantity;
    /**
     * @var Admin[]
     */
    private array $owners;
}