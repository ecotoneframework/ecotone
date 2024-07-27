<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRevision;

final class RegisterPerson
{
    public function __construct(
        private string $personId,
        private string $type
    ) {
    }

    public function getPersonId(): string
    {
        return $this->personId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
