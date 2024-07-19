<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

/**
 * licence Apache-2.0
 */
class Product
{
    private string $name;

    private ?int $quantity;
    /**
     * @var Admin[]
     */
    private array $owners;
}
