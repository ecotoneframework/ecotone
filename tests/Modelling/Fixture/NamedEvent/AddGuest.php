<?php

namespace Ecotone\Tests\Modelling\Fixture\NamedEvent;

class AddGuest
{
    public function __construct(private string $bookId, private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }
}