<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
final class User
{
    #[Identifier]
    private string $userId;

    #[CommandHandler]
    public static function register(RegisterUser $command): self
    {
        $user = new self();
        $user->userId = $command->userId;

        return $user;
    }

    #[QueryHandler('user.get')]
    public function isRegistered(): bool
    {
        return true;
    }
}
