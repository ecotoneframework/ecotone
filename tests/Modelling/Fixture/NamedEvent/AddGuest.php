<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

/**
 * licence Apache-2.0
 */
class AddGuest
{
    public function __construct(private string $bookId, private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
