<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\WithAggregateEvents;

#[Aggregate]
class GuestBook
{
    use WithAggregateEvents;

    private function __construct(#[AggregateIdentifier] private string $bookId, private array $guests) {}

    #[CommandHandler]
    public static function registerBook(RegisterBook $command) : self
    {
        return new self($command->getBookId(), []);
    }

    #[CommandHandler]
    public function addGuest(AddGuest $command) : void
    {
        $this->recordThat(new GuestWasAddedToBook($this->bookId, $command->getName()));
    }

    public function getId() : string
    {
        return $this->bookId;
    }
}