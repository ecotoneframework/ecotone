<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
class Shop
{
    #[Asynchronous('shop')]
    #[CommandHandler('storeCoins', 'storeCoinsEndpoint')]
    public function buy(int $command): int
    {
        return $command;
    }
}
