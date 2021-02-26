<?php


namespace Ecotone\EventSourcing\Attribute;

use Ecotone\Messaging\Support\Assert;

#[\Attribute]
class EventSourcedEvent
{
    private string $name;

    public function __construct(string $name)
    {
        Assert::notNullAndEmpty($name, "Name for event should not be empty, otherwise remove attribute.");

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}