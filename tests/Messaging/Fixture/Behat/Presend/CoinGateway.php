<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * @MessageEndpoint()
 */
interface CoinGateway
{
    /**
     * @Gateway(requestChannel="storeCoins")
     */
    public function store(int $coins);
}