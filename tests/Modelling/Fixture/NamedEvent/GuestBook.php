<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithEvents;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class GuestBook
{
    use WithEvents;

    private function __construct(#[Identifier] private string $bookId, private array $guests)
    {
    }

    #[CommandHandler]
    public static function registerBook(RegisterBook $command): self
    {
        return new self($command->getBookId(), []);
    }

    #[CommandHandler]
    public function addGuest(AddGuest $command): void
    {
        $this->recordThat(new GuestWasAddedToBook($this->bookId, $command->getName()));
    }

    public function getId(): string
    {
        return $this->bookId;
    }
}
