<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRevision;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\WithAggregateVersioning;

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

    #[Apply]
    public function applyPersonWasRegistered(
        PersonWasRegistered $event,
        #[Header(MessageHeaders::REVISION)] int $revision
    ): void {
        $this->personId = $event->getPersonId();
        $this->type = $event->getType();
        $this->registeredWithRevision = $revision;
    }
}
