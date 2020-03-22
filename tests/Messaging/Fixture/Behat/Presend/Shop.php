<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * @MessageEndpoint()
 */
class Shop
{
    /**
     * @CommandHandler(endpointId="storeCoinsEndpoint", inputChannelName="storeCoins")
     * @Async("shop")
     */
    public function buy(int $command): int
    {
        return $command;
    }
}