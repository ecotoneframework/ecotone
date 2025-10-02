<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Modelling\Attribute\CommandHandler;

class IncorrectService
{
    #[CommandHandler('incorrectFetchAggregate')]
    public function handleIncorrectFetchAggregate(
        ComplexCommand $command,
        #[Fetch('payload.email')] int $user
    ): void {
    }
}
