<?php


namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::EVENT_NAME)]
class GuestWasAddedToBook
{
    const EVENT_NAME = "book.guest_was_added";

    public function __construct(private string $bookId, private string $name) {}

    public function getBookId(): string
    {
        return $this->bookId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}