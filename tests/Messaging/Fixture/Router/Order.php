<?php

namespace Test\Ecotone\Messaging\Fixture\Router;

/**
 * Class Order
 * @package Test\Ecotone\Messaging\Fixture\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Order
{
    /**
     * @var string
     */
    private $number;

    /**
     * Order constructor.
     * @param string $number
     */
    private function __construct(string $number)
    {
        $this->number = $number;
    }

    public static function create(string $number) : self
    {
        return new self($number);
    }
}