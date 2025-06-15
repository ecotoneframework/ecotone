<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Enterprise
 */
class ComplexService
{
    private array $results = [];

    #[CommandHandler]
    public function handleComplexCommand(
        ComplexCommand $command,
        #[Fetch("reference('identifierMapper').map(payload.email)")] User $user
    ): void {
        $this->results[] = [
            'command' => $command,
            'user' => $user,
            'userName' => $user?->getName(),
        ];
    }

    #[CommandHandler('handleWithArrayIdentifiers')]
    public function handleWithArrayIdentifiers(
        ComplexCommand $command,
        #[Fetch("{'userId': reference('identifierMapper').map(payload.email)}")] User $user
    ): void {
        $this->results[] = [
            'command' => $command,
            'user' => $user,
            'userName' => $user?->getName(),
        ];
    }

    #[CommandHandler('incorrectFetchAggregate')]
    public function handleIncorrectFetchAggregate(
        ComplexCommand $command,
        #[Fetch('payload.email')] int $user
    ): void {
        $this->results[] = [
            'command' => $command,
            'user' => $user,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getLastResult(): ?array
    {
        return end($this->results) ?: null;
    }
}
