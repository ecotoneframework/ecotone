<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class GuestViewer
{
    const BOOK_GET_GUESTS = "book.getGuests";
    private array $guests = [];

    #[EventHandler(GuestWasAddedToBook::EVENT_NAME)]
    public function addGuest(GuestWasAddedToBook $event)
    {
        $this->guests[$event->getBookId()][] = $event->getName();
    }

    #[QueryHandler(self::BOOK_GET_GUESTS)]
    public function getGuests(string $bookId) : array
    {
        return $this->guests[$bookId] ?? [];
    }
}