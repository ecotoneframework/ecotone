<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRevision;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
final class Person
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $personId;
    private string $type;
    private int $registeredWithRevision = 0;

    #[CommandHandler]
    public static function register(RegisterPerson $command): array
    {
        return [
            new PersonWasRegistered(
                $command->getPersonId(),
                $command->getType(),
            ),
        ];
    }

    public function getPersonId(): string
    {
        return $this->personId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRegisteredWithRevision(): int
    {
        return $this->registeredWithRevision;
    }

    #[EventSourcingHandler]
    public function applyPersonWasRegistered(
        PersonWasRegistered $event,
        #[Header(MessageHeaders::REVISION)] int $revision
    ): void {
        $this->personId = $event->getPersonId();
        $this->type = $event->getType();
        $this->registeredWithRevision = $revision;
    }
}
