<?php


namespace Ecotone\Tests\Messaging\Fixture\Conversion;


use Ecotone\Tests\Messaging\Fixture\Conversion\Admin;

class Product
{
    private string $name;

    private ?int $quantity;
    /**
     * @var Admin[]
     */
    private array $owners;
}