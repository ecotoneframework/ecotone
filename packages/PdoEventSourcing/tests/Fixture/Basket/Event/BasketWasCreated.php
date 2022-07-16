<?php

namespace Test\Ecotone\EventSourcing\Fixture\Basket\Event;

use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::EVENT_NAME)]
class BasketWasCreated
{
    public const EVENT_NAME = 'basket.was_created';

    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
