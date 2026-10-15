<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class User
{
    #[Identifier]
    private string $userId;

    #[CommandHandler(routingKey: RegisterUser::class)]
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
