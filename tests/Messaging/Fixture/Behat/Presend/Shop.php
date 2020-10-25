<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class Shop
{
    #[Asynchronous("shop")]
    #[CommandHandler("storeCoins", "storeCoinsEndpoint")]
    public function buy(int $command): int
    {
        return $command;
    }
}