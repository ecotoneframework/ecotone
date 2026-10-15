<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class GuestViewer
{
    public const BOOK_GET_GUESTS = 'book.getGuests';
    private array $guests = [];

    #[EventHandler(GuestWasAddedToBook::EVENT_NAME)]
    public function addGuest(GuestWasAddedToBook $event)
    {
        $this->guests[$event->getBookId()][] = $event->getName();
    }

    #[QueryHandler(self::BOOK_GET_GUESTS)]
    public function getGuests(string $bookId): array
    {
        return $this->guests[$bookId] ?? [];
    }
}
